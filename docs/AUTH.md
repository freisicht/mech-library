# Authentication & Army Visibility

The whole web UI requires a login. Two providers are available, selected with
`AUTH_PROVIDER` in `.env`:

| Provider     | Who can log in                           | What a "group" is            |
| ------------ | ---------------------------------------- | ---------------------------- |
| `local`      | Accounts created with `bin/console user:add` | Names assigned with `user:groups` |
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

## Mattermost (`AUTH_PROVIDER=mattermost`)

1. `/auth/start` kicks off OAuth2 with state; Mattermost calls back to
   `/auth/callback`.
2. On callback the user is upserted into `users` (keyed on `provider` +
   `external_id`, the Mattermost user ID), the access token is stored in the
   session, and the user's channel IDs are cached on the row (`groups_json` +
   `groups_refreshed_at`).
3. On every subsequent request, `AuthMiddleware` re-uses the cached channels
   if fresher than **5 min**, otherwise refreshes from Mattermost using the
   stored token. Mattermost failures keep the stale cache rather than logging
   the user out.

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
- `App\Auth\GroupSource`: where groups come from; `MattermostGroupSource`
  and `LocalGroupSource` implement it.
- `App\Web\Controller\AuthController`: login page, local login, logout.
- `App\Web\Controller\MattermostAuthController`: OAuth start and callback.
  Routes for the inactive provider are not registered.

A new provider needs a `GroupSource`, its login routes and an entry in
`AuthConfig::resolve()`.

## Testing

Auth is bypassed in web tests by setting `AUTH_DISABLED=1` (done automatically
in `WebTestCase::setUp`). In that mode, the middleware passes all requests
through with no `CurrentUser`, so only public armies are visible.
`tests/Auth/LocalLoginTest` exercises the real local login flow.
