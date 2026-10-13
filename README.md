# firstduefiretruckparty.com – LIVE branch

Production build of the static site. Every push to this branch deploys to https://firstduefiretruckparty.com/ through `.github/workflows/deploy-live.yml`.

- Staging is on `main` and deploys to `/staging/` and GitHub Pages.
- Nothing on the server is deleted. The old WordPress `.htaccess` is saved once as `public_html/.htaccess.wp-backup-<date>`.
- Rollback: restore that file as `public_html/.htaccess`, and WordPress serves the site again.
