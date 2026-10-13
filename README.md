# firstduefiretruckparty.com – LIVE branch

Production build of the static site. Every push to this branch deploys to https://firstduefiretruckparty.com/ through `.github/workflows/deploy-live.yml`.

- Staging is on `main` and deploys to `/staging/` and GitHub Pages.
- Deploys only add or replace files and never delete anything on the server. The `/staging/` folder is left alone.
- When this went live on 2026-10-13, `public_html` contained only `staging/` (no WordPress files), so nothing was replaced.
