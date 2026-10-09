# firstduefiretruckparty.com – staging

Static HTML staging build of the 2026 redesign for **1st Due Fire Truck Party and Events**.

- Served with GitHub Pages from the `main` branch (root).
- Staging only: every page is `noindex,nofollow`, and `robots.txt` blocks crawlers.
- The quote form is disabled here because GitHub Pages can't run PHP. The production package includes `send-quote.php`.
- Photos load from the live site's `/wp-content/uploads/`.

## Deploy to DirectAdmin (/staging)

Every push to `main` also uploads the site to https://firstduefiretruckparty.com/staging/ through `.github/workflows/deploy-directadmin.yml`. The workflow skips the upload until these repository secrets exist: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`. Two optional secrets override the defaults: `FTP_SERVER_DIR` (default `domains/firstduefiretruckparty.com/public_html/staging/`) and `FTP_PROTOCOL` (default `ftps`). On that copy the quote form works, and its emails are tagged `[STAGING TEST]`.
