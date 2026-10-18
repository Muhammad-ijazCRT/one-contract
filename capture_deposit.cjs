const { chromium } = require('playwright');
const path = require('path');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 390, height: 844 },
        deviceScaleFactor: 2
    });
    const page = await context.newPage();

    // 1. Login with country_id
    console.log('Navigating to login...');
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'user@one-contract.com');
    await page.evaluate(() => {
        const countryInput = document.getElementById('country_id');
        if (countryInput) countryInput.value = 'AF';
    });
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle' }).catch(() => {});

    // 2. Go to Deposit
    console.log('Navigating to deposit page...');
    await page.goto('http://127.0.0.1:8000/dashboard/deposit', { waitUntil: 'networkidle' });

    // 3. Capture Crypto Tab
    console.log('Taking screenshot of Crypto Deposit...');
    await page.screenshot({ path: path.join(__dirname, 'public/images/deposit/v2/test_crypto_live.png'), fullPage: false });

    // 4. Click Fiat Tab
    console.log('Clicking Fiat Tab...');
    await page.click('[data-ocdep2-tab="fiat"]');
    await page.waitForTimeout(400);

    // 5. Capture Fiat Tab
    console.log('Taking screenshot of Fiat Deposit...');
    await page.screenshot({ path: path.join(__dirname, 'public/images/deposit/v2/test_fiat_live.png'), fullPage: false });

    await browser.close();
    console.log('Done screenshots!');
})();
