# Agent Workflow — Worktree, Verification, Auto-Merge

This document is **binding for all Claude Code agents** working in this repo.
It defines how a task moves from worktree → commit → main, what counts as
"verification passed", and which steps an agent may perform automatically vs.
which require user confirmation.

It supersedes the older "Agents may commit only when the user has asked" rule
in `CLAUDE.md` for tasks that follow the standard workflow below. `CLAUDE.md`
points here.

## TL;DR

```
1. Set up the worktree once  →  bin/setup-worktree
2. Implement on the branch
3. Run  composer test        →  must pass green
4. (UI changes) verify in the browser against the main checkout
   — but only AFTER step 5 because the web server serves the main checkout, not worktrees
5. git commit  (auto, when verification is green)
6. git merge into main, fast-forward, from the main checkout
7. UI smoke test in browser if relevant
8. Stop. Do not push to origin unless the user asks.
```

## Where things live

| Path                                   | Role                                                     |
| -------------------------------------- | -------------------------------------------------------- |
| `<main>/`                              | **Main checkout.** Web server docroot. Has `vendor/`, `.env` |
| `<main>/.claude/worktrees/<name>/`     | Per-task worktrees on feature branches                   |
| your local URL for `<main>/public/`    | The web server serves the **main checkout only**         |

`<main>` is wherever the repo is cloned; `git worktree list` prints it first.

The MySQL DB is shared between main and all worktrees (one local instance).
Schema changes from a worktree affect everyone immediately — be careful.

## Worktree setup (one-time per worktree)

A fresh worktree has no `vendor/` and no `.env`. The agent's first action in a
new worktree is:

```sh
bin/setup-worktree
```

This script:

1. Runs `composer install` (≈3s thanks to the package cache)
2. Symlinks `.env` from the main checkout (DB credentials etc. shared)
3. Verifies `php -v` and `composer test` are runnable

Why not symlink `vendor/` from main? Because Composer's autoload resolves
`__DIR__`-relative paths through the symlink to the *main* repo's `src/`. Tests
would then validate the main code instead of the worktree's modified files —
silently passing while bugs ship. A real per-worktree `composer install` is
the only safe option.

`vendor/` and `.env` are already in `.gitignore`.

## Verification — what the agent must run before committing

The single command:

```sh
composer test
```

…runs three gates, all of which **must pass** before the agent commits:

| Gate         | Command                              | Failure means                          |
| ------------ | ------------------------------------ | -------------------------------------- |
| `cs-check`   | `php-cs-fixer fix --dry-run --diff`  | Style violation. Run `composer cs-fix` |
| `stan`       | `phpstan analyse src tests --level=6`| Type error / undefined symbol          |
| `phpunit`    | full unit test suite                 | Behaviour regression                   |

`composer test` exit code is the source of truth. PHPStan deprecation notices
in stderr (e.g. "PHPStan 2.x is available") are informational and do not
constitute failure.

For changes that touch only Twig templates, CSS, or JS, the agent still runs
`composer test` — the cs/stan gates are no-ops there but phpunit still has
to be green.

### What the agent does NOT verify automatically

- **UI behaviour in a real browser.** The web server serves only the main checkout;
  the worktree is not reachable via HTTP. The agent merges first (step 6),
  then either does a manual browser check (if a browser-tool is available) or
  asks the user to look. The agent never claims a UI feature works without
  having actually loaded the page.
- **Schema migrations against production data.** Schema changes go via a
  separate handoff to the user.
- **External HTTP calls to MUL.** Live scrapes are never run as part of
  verification.

## Commit — automatic on green verification

When `composer test` passes, the agent commits without further confirmation,
following these rules:

- One logical change per commit
- Conventional Commits format: `feat:`, `fix:`, `refactor:`, `docs:`, `chore:`,
  `test:`. Imperative mood, ≤72 char subject. Body explains *why* if non-trivial.
- `Co-Authored-By: Claude …` trailer per the standard Claude Code template
- **Never** `--no-verify`, never `--amend` a pushed commit, never commit `.env`
  or `vendor/`
- Stage files explicitly (`git add path/to/file`). Avoid `git add -A` / `git add .`

If verification fails, the agent **does not commit**. It reports the failure,
fixes the underlying issue, and re-runs `composer test`. If the agent cannot
fix the failure within reasonable effort, it stops and asks the user.

## Merge to main — automatic, fast-forward only

After committing on the feature branch, the agent merges to `main` from the
**main checkout**, not from inside the worktree:

```sh
cd <main>
git fetch       # if remote exists
git checkout main
git merge --ff-only <feature-branch>
```

Rules:

- Fast-forward only. If `main` has moved and ff is impossible, the agent
  stops and asks the user — it never auto-rebases shared history.
- After merge, the agent runs `composer test` **again** in the main checkout
  to catch any state drift between worktree and main (different `vendor/`,
  forgotten files, etc.). If it fails, the agent reverts the merge with
  `git reset --hard <pre-merge-sha>` (safe because no push yet) and reports.
- Never force-push. Never push at all unless the user asks.
- Never delete the feature branch automatically — keep it for reference until
  the user (or a follow-up task) tidies up.

## Tags and phase boundaries

Phase tags (`v0.<n>.0`) are still set by the user, not by agents. CLAUDE.md
rule unchanged.

## Decision: why this workflow?

Considered alternatives:

1. **Run tests in main checkout against worktree files.** Rejected — autoload
   would resolve `App\*` to main's `src/`, so tests would silently validate
   the wrong source files.
2. **Symlink `vendor/` from main into the worktree.** Same problem.
3. **Skip per-worktree setup; merge first, test in main.** Rejected — putting
   unverified code on `main` even temporarily is exactly what we want to avoid.
4. **Per-worktree `composer install`.** **Chosen.** Cheap (~3s with cache),
   correct, isolated. The 155 MB on disk per worktree is fine — worktrees are
   short-lived.

For UI changes the only honest verification is a real browser load against
the main checkout. That is why merge precedes UI verification, and why an
agent that touches the UI must explicitly say so when reporting back.
