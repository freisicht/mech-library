# Authentication & Army Visibility

The whole web UI requires a login. Two providers are available, selected with
`AUTH_PROVIDER` in `.env`:

| Provider     | Who can log in                           | What a "group" is            |
| ------------ | ---------------------------------------- | ---------------------------- |
| `local`      | Accounts created with `bin/console user:add` | Names assigned with `user:groups` |
| `discord`    | Members of the servers in `DISCORD_ALLOWED_GUILDS` (OAuth2) | Those Discord servers |
| `mattermost` | Anyone with an account on `MM_BASE_URL` (OAuth2) | Mattermost channels the user is in |

Without `AUTH_PROVIDER` the app uses `mattermost` if `MM_BASE_URL` is set and
`local` otherwise, so existing Mattermost installations keep working unchanged.

Armies can be restricted to one group, so a single instance can serve several
gaming groups.

## Local accounts (`AUTH_PROVIDER=local`)

No external service needed. Manage users on the command line:

```bash
bin/console user:add alice --group=tuesday     # asks for the password twice
bin/console user:add bob --group=tuesday --group=league
bin/console user:password alice                # set a new password
bin/console user:groups alice                  # show groups
bin/console user:groups alice tuesday league   # replace groups
bin/console user:groups alice --clear
bin/console user:list
```

`user:add` and `user:password` accept `--password-stdin` for scripts. Passwords
need at least 8 characters and are stored with `password_hash()`. Usernames are
case-insensitive. Groups are free-form names of up to 64 characters.

The login form is protected by a CSRF token, and a failed attempt is answered
only after a one-second delay. There is no lockout; put the instance behind
HTTPS and, if it is reachable from the internet, consider fail2ban on the
web server log.

## OAuth providers: how it works

Discord and Mattermost share one flow (`OAuthController`):

1. `/auth/start` kicks off OAuth2 with state; the provider calls back to
   `/auth/callback`.
2. On callback the access token is stored in the session and the user's
   groups are fetched. If the provider says these groups do not grant access
   (Discord: no allowed server), the login is refused and nothing is stored.
3. Otherwise the user is upserted into `users` (keyed on `provider` +
   `external_id`, the provider's user ID) and the group IDs are cached on the
   row (`groups_json` + `groups_refreshed_at`).
4. On every subsequent request, `AuthMiddleware` re-uses the cached groups
   if fresher than **5 min**, otherwise refreshes them with the stored token.
   Provider failures keep the stale cache rather than logging the user out.
   A user who no longer passes the access check (left all allowed Discord
   servers) is logged out on the next refresh.

## Discord (`AUTH_PROVIDER=discord`)

Anyone can create a Discord account, so access is limited to members of the
servers listed in `DISCORD_ALLOWED_GUILDS`; the setting is required. Those
servers are also the groups for the army gate, so one instance can serve
several gaming groups with their own Discord servers. Other servers a user is
in are never stored. No bot is needed; the app only asks for the `identify`
and `guilds` scopes, not for the e-mail address.

### Application setup (one-time)

1. On <https://discord.com/developers/applications> create a **New Application**.
2. Under **OAuth2**, add the redirect `https://mechlib.example.com/auth/callback`
   and copy the **Client ID** and **Client Secret** (reset it once to see it).
3. Get the server IDs: in Discord, enable *User Settings → Advanced →
   Developer Mode*, then right-click a server icon → **Copy Server ID**.

```
AUTH_PROVIDER=discord
DISCORD_CLIENT_ID=…
DISCORD_CLIENT_SECRET=…
DISCORD_REDIRECT_URI=https://mechlib.example.com/auth/callback
DISCORD_ALLOWED_GUILDS=123456789012345678,234567890123456789
```

Restricting by server role is not supported; it would need a bot or a much
more rate-limited API call per server.

## Mattermost (`AUTH_PROVIDER=mattermost`)

Everyone with an account on the Mattermost server can log in. Groups are the
user's channels across all teams (direct messages excluded).

### OAuth app setup (one-time)

In Mattermost System Console → Integrations → OAuth 2.0 Applications:

- Name: `Mech Library`
- Callback URL: `https://mechlib.example.com/auth/callback`
- Get the **Client ID** and **Client Secret**

Then set in `.env` (`MM_BASE_URL` and `MM_OAUTH_REDIRECT_URI` are required; the
app refuses to start the login flow without them):

```
AUTH_PROVIDER=mattermost
MM_BASE_URL=https://mattermost.example.com
MM_OAUTH_CLIENT_ID=…
MM_OAUTH_CLIENT_SECRET=…
MM_OAUTH_REDIRECT_URI=https://mechlib.example.com/auth/callback
```

## Sessions

PHP's native sessions, cookie name `mechlib_sid`, `HttpOnly`, `SameSite=Lax`,
`Secure` when served over HTTPS. A session only counts for users of the active
provider, so switching `AUTH_PROVIDER` logs everyone out.

## Army-level group gate

`armies.required_group_id` (nullable):

- `NULL` → visible to every authenticated user.
- non-`NULL` → visible only to users whose cached groups contain that ID.

Enforcement lives in `App\Auth\ArmyVisibility` and is applied as a SQL
`WHERE` fragment in `ArmyRepository` and `FormationRepository` (formations
inherit their army's visibility; standalone formations have no army gate).

The force panel offers the current user's groups in a dropdown next to each
army. Via the API:

```
PUT /api/armies/{id}        { "name": "Recon", "required_group_id": "tuesday" }
GET /api/me/groups          groups of the current user (id + display_name)
```

Setting `required_group_id` to `""` clears the gate.

## Code map

- `App\Auth\AuthConfig`: resolves the provider from the environment.
- `App\Auth\GroupSource`: where groups come from and whether they grant
  access; one implementation per provider.
- `App\Auth\OAuthBackend`: provider-specific half of an OAuth login (the
  league/oauth2-client provider and how to read the user's identity).
- `App\Web\Controller\AuthController`: login page, local login, logout.
- `App\Web\Controller\OAuthController`: OAuth start and callback.
  Routes for the inactive login type are not registered.

A new OAuth provider (e.g. generic OIDC) needs an `OAuthBackend`, a
`GroupSource`, container wiring in `ContainerFactory` and an entry in
`AuthConfig::resolve()`.

## Testing

Auth is bypassed in web tests by setting `AUTH_DISABLED=1` (done automatically
in `WebTestCase::setUp`). In that mode, the middleware passes all requests
through with no `CurrentUser`, so only public armies are visible.
`tests/Auth/LocalLoginTest` exercises the real local login flow,
`tests/Auth/DiscordLoginTest` the OAuth callback against mocked Discord responses.
