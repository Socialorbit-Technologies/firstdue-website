/**
 * 1st Due Fire Truck Party – website lead log
 *
 * Paste this into the Apps Script editor of the "First Due Fire Truck Leads"
 * Google Sheet (Extensions > Apps Script), then Deploy > New deployment >
 * Web app, Execute as: Me, Who has access: Anyone. Copy the web app URL into
 * the repo secret LEADS_SHEET_URL and run the "Write mail config" workflow.
 *
 * send-quote.php posts each quote request here as JSON. The web app URL is
 * only stored on the server (mail-config.php, above public_html), never in
 * the site's pages.
 */
var SHEET_NAME = 'Leads';

function doPost(e) {
  var lock = LockService.getScriptLock();
  lock.waitLock(10000);
  try {
    var d = JSON.parse(e.postData.contents || '{}');
    var sheet = SpreadsheetApp.getActive().getSheetByName(SHEET_NAME);
    sheet.appendRow([
      new Date(),
      d.name || '', "'" + (d.phone || ''), d.email || '',
      d.service || '', d.event_date || '', d.location || '',
      d.guests || '', d.message || '', d.page || '',
      d.site || '', d.email_sent ? 'Yes' : 'NO – check inbox setup', d.ip || ''
    ]);
    return ContentService.createTextOutput('ok');
  } catch (err) {
    return ContentService.createTextOutput('error: ' + err);
  } finally {
    lock.releaseLock();
  }
}

function doGet() {
  return ContentService.createTextOutput('1st Due lead log is running.');
}
