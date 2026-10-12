# Authentication & Army Visibility

The whole web UI is gated behind Mattermost OAuth2. Any user who can log in to
the Mattermost server configured in `MM_BASE_URL` can use the site. Per-army access can be further restricted
to members of a specific Mattermost channel.

## How it works

1. Unauthenticated request → `AuthMiddleware` redirects HTML to
   `/auth/login`, API requests get `401 JSON`.
2. `/auth/start` kicks off OAuth2 with state; Mattermost calls back to
   `/auth/callback`.
3. On callback: user is upserted into `users` (keyed on `mm_user_id`), the
   access token is stored in session, and the user's channel IDs are fetched
   once and cached on the row (`channels_json` + `channels_refreshed_at`).
4. On every subsequent request, `AuthMiddleware` re-uses the cached channels
   if fresher than **5 min**, otherwise refreshes from Mattermost using the
   stored token. MM failures keep the stale cache rather than logging the
   user out.

Sessions use PHP's native sessions, cookie name `mechlib_sid`, `HttpOnly`,
`SameSite=Lax`, `Secure` when served over HTTPS.

## Army-level channel gate

`armies.required_mm_channel_id` (nullable):

- `NULL` → visible to every authenticated user.
- non-`NULL` channel ID → visible only to users whose cached membership
  contains that channel.

Enforcement lives in `App\Auth\ArmyVisibility` and is applied as a SQL
`WHERE` fragment in `ArmyRepository` and `FormationRepository` (formations
inherit their army's visibility; standalone formations have no army gate).

## Mattermost OAuth app setup (one-time, by user)

In Mattermost System Console → Integrations → OAuth 2.0 Applications:

- Name: `Mech Library`
- Callback URL: `https://mechlib.example.com/auth/callback`
  (and/or the matching `http://localhost/.../auth/callback` for dev)
- Get the **Client ID** and **Client Secret**

Then set in `.env` (`MM_BASE_URL` and `MM_OAUTH_REDIRECT_URI` are required; the
app refuses to start the login flow without them):

```
MM_BASE_URL=https://mattermost.example.com
MM_OAUTH_CLIENT_ID=…
MM_OAUTH_CLIENT_SECRET=…
MM_OAUTH_REDIRECT_URI=https://mechlib.example.com/auth/callback
```

## Setting an army's required channel

`PUT /api/armies/{id}` with body:

```json
{ "name": "Recon", "required_mm_channel_id": "abc123channelid" }
```

To list channels available for the current user (id + display name):

```
GET /api/me/channels
```

Setting `required_mm_channel_id` to `""` or omitting it clears the gate.

## Testing

Auth is bypassed in unit tests by setting `MM_AUTH_DISABLED=1` (done
automatically in `WebTestCase::setUp`). In that mode, the middleware passes
all requests through with no `CurrentUser`, so visibility rules treat the
caller as having no channel memberships (only public armies are visible).
