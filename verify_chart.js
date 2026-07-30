const { chromium } = require('playwright');

(async () => {
	const browser = await chromium.launch({ headless: true });
	const context = await browser.newContext({ ignoreHTTPSErrors: true });
	const page = await context.newPage();
	await page.goto('http://localhost:81/index.php/apps/charity');
	await page.fill('input[name="user"]', 'admin');
	await page.fill('input[name="password"]', 'clayoven99_99');
	await page.click('button[type="submit"]');
	await page.waitForURL('**/apps/charity/**', { timeout: 10000 });
	await page.waitForSelector('.cm-bar-chart__months', { timeout: 15000 });
	const chart = await page.$('.cm-bar-chart__months');
	const boundingBox = await chart.boundingBox();
	await chart.screenshot({ path: '/home/habib/workspace/nextcloud_cm/apps/charity/screenshot.png' });
	console.log('Chart rendered, bounding box:', boundingBox);
	await browser.close();
})();
