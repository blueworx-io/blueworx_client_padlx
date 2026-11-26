// The Founding Membership page: the sign-up form while spots are open, and the
// waitlist once all 150 are taken. Each waitlist sign-up must reach PadlX.

const { test, expect } = require('@playwright/test');
const { setSpots, setClosed, ensureSignupPage, clearWaitlist, readWaitlist, readMail } = require('./helpers');

let pageUrl;

test.beforeAll(() => {
  pageUrl = ensureSignupPage();
  setClosed(false);
});

test.beforeEach(() => {
  clearWaitlist();
});

test.afterAll(() => {
  setSpots(null);
  setClosed(false);
  clearWaitlist();
});

async function fillWaitlist(page, person) {
  await page.fill('#bwpx-first_name', person.first);
  await page.fill('#bwpx-last_name', person.last);
  await page.fill('#bwpx-email', person.email);
  await page.fill('#bwpx-mobile', person.mobile);
}

test.describe('spots are open', () => {
  test('shows the sign-up form, not the waitlist', async ({ page }) => {
    setSpots(42);
    await page.goto(pageUrl);

    await expect(page.getByText('STRICTLY LIMITED · 150 FOUNDING MEMBERS')).toBeVisible();
    await expect(page.locator('#bwpx-form')).toBeVisible();
    await expect(page.locator('#bwpx-waitlist')).toHaveCount(0);
    await expect(page.getByText('Join the waitlist')).toHaveCount(0);
  });

  test('stays a sign-up form when SureCart is not limiting the spots', async ({ page }) => {
    setSpots(null);
    await page.goto(pageUrl);

    await expect(page.locator('#bwpx-form')).toBeVisible();
    await expect(page.locator('#bwpx-waitlist')).toHaveCount(0);
  });

  test('the last spot still counts as open', async ({ page }) => {
    setSpots(1);
    await page.goto(pageUrl);

    await expect(page.locator('#bwpx-form')).toBeVisible();
  });
});

test.describe('all 150 spots are taken', () => {
  test.beforeEach(() => {
    setSpots(0);
  });

  test('shows a waitlist sign-up instead of a sold-out message', async ({ page }) => {
    await page.goto(pageUrl);

    await expect(page.getByRole('heading', { name: 'Join the founding waitlist.' })).toBeVisible();
    await expect(page.getByText('ALL 150 SPOTS TAKEN')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Join the waitlist' })).toBeVisible();
    await expect(page.locator('#bwpx-form')).toHaveCount(0);
    await expect(page.locator('body')).not.toContainText(/sold out/i);
  });

  test('a sign-up is saved and emailed to PadlX', async ({ page }) => {
    await page.goto(pageUrl);
    // The form refuses anything sent faster than a person could fill it in.
    await page.waitForTimeout(3500);
    await fillWaitlist(page, { first: 'Wendy', last: 'Waitlist', email: 'Wendy.Waitlist@example.com', mobile: '0400 000 111' });
    await page.getByRole('button', { name: 'Join the waitlist' }).click();

    await expect(page.getByRole('heading', { name: "You're on the waitlist." })).toBeVisible();

    const list = readWaitlist();
    expect(Object.keys(list)).toEqual(['wendy.waitlist@example.com']);
    expect(list['wendy.waitlist@example.com']).toMatchObject({ first_name: 'Wendy', last_name: 'Waitlist', mobile: '0400 000 111' });

    const mail = readMail();
    expect(mail).toHaveLength(1);
    expect(mail[0].to).toBe('info@padlx.com.au');
    expect(mail[0].subject).toBe('Founding waitlist: Wendy Waitlist');
    expect(mail[0].message).toContain('wendy.waitlist@example.com');
    expect(mail[0].message).toContain('0400 000 111');
  });

  test('the same person joining twice is listed once and emailed once', async ({ page }) => {
    for (let i = 0; i < 2; i++) {
      await page.goto(pageUrl);
      await page.waitForTimeout(3500);
      await fillWaitlist(page, { first: 'Dee', last: 'Double', email: 'dee@example.com', mobile: '0400 000 222' });
      await page.getByRole('button', { name: 'Join the waitlist' }).click();
      await expect(page.getByRole('heading', { name: "You're on the waitlist." })).toBeVisible();
    }

    expect(Object.keys(readWaitlist())).toEqual(['dee@example.com']);
    expect(readMail()).toHaveLength(1);
  });

  test('a bad email is sent back with a message and nothing is saved', async ({ page }) => {
    await page.goto(pageUrl);
    await page.waitForTimeout(3500);
    await fillWaitlist(page, { first: 'Bad', last: 'Email', email: 'not-an-email', mobile: '0400 000 333' });
    // The browser would stop an invalid email itself; send it anyway to check the server does too.
    await page.locator('#bwpx-waitlist').evaluate((form) => form.setAttribute('novalidate', ''));
    await page.getByRole('button', { name: 'Join the waitlist' }).click();

    await expect(page.getByText('Please enter a valid email address.')).toBeVisible();
    expect(readWaitlist()).toEqual({});
    expect(readMail()).toHaveLength(0);
  });

  test('someone who started the sign-up form before the last spot went is kept as a lead', async ({ page }) => {
    setSpots(1);
    await page.goto(pageUrl);
    await expect(page.locator('#bwpx-form')).toBeVisible();
    await page.waitForTimeout(3500);

    // Someone else takes the last spot while this form is open.
    setSpots(0);

    const form = await page.locator('#bwpx-form').evaluate((el) => ({
      action: el.getAttribute('action'),
      data: Object.fromEntries(new FormData(el).entries()),
    }));
    const response = await page.request.post(form.action, {
      form: {
        ...form.data,
        'bwpx[first_name]': 'Late',
        'bwpx[last_name]': 'Lead',
        'bwpx[email]': 'late@example.com',
        'bwpx[mobile]': '0400 000 444',
      },
    });
    expect(response.ok()).toBeTruthy();
    expect(response.url()).toContain('bwpx=waitlisted');

    expect(Object.keys(readWaitlist())).toEqual(['late@example.com']);
    expect(readMail().map((m) => m.subject)).toEqual(['Founding waitlist: Late Lead']);
  });
});

test.describe('founding sales have closed', () => {
  test('shows the closed version of the waitlist, still collecting details', async ({ page }) => {
    setSpots(42);
    setClosed(true);
    try {
      await page.goto(pageUrl);
      await expect(page.getByRole('heading', { name: 'Founding memberships have closed.' })).toBeVisible();
      await expect(page.getByRole('button', { name: 'Keep me posted' })).toBeVisible();
    } finally {
      setClosed(false);
    }
  });
});
