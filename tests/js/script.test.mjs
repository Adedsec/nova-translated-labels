import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { test } from 'node:test';
import { JSDOM } from 'jsdom';

const script = readFileSync(new URL('../../dist/nova-translated-labels.js', import.meta.url), 'utf8');
const MARKER = '⁣';

// Same output as Label::make() in PHP.
const mark = (label, realName) => `${label}${MARKER} (${realName})${MARKER}`;

// Mutation observer callbacks run as microtasks, so one macrotask lets them all finish.
const settle = () => new Promise((resolve) => setTimeout(resolve, 0));

const page = async (body, title = 'Nova') => {
  const dom = new JSDOM(`<!doctype html><html><head><title>${title}</title></head><body>${body}</body></html>`, {
    runScripts: 'outside-only',
  });

  dom.window.eval(script);
  await new Promise((resolve) => dom.window.addEventListener('load', resolve));
  await settle();

  return dom.window.document;
};

test('draws the real name as a grey line under a label', async () => {
  const document = await page(`<label>${mark('عنوان', 'title')}</label>`);
  const label = document.querySelector('label');

  assert.equal(label.firstChild.nodeValue, 'عنوان');
  assert.equal(label.querySelector('.ntl-subtitle').textContent, 'title');
});

test('removes the real name outside the places that show a subtitle', async () => {
  const document = await page(`<button>${mark('عنوان', 'title')}</button>`);

  assert.equal(document.querySelector('button').textContent, 'عنوان');
  assert.equal(document.querySelector('.ntl-subtitle'), null);
});

test('leaves native dropdown options inline', async () => {
  const document = await page(`<select><option>${mark('پیش‌نویس', 'draft')}</option></select>`);

  assert.equal(document.querySelector('option').textContent, mark('پیش‌نویس', 'draft'));
});

test('draws a real name that contains a closing bracket', async () => {
  const document = await page(`<label>${mark('وزن', 'Weight (kg)')}</label>`);

  assert.equal(document.querySelector('label').firstChild.nodeValue, 'وزن');
  assert.equal(document.querySelector('.ntl-subtitle').textContent, 'Weight (kg)');
});

test('removes the real name from placeholders, titles and aria labels', async () => {
  const document = await page(`<input placeholder="${mark('عنوان', 'title')}" title="${mark('عنوان', 'title')}" aria-label="${mark('عنوان', 'title')}">`);
  const input = document.querySelector('input');

  assert.equal(input.getAttribute('placeholder'), 'عنوان');
  assert.equal(input.getAttribute('title'), 'عنوان');
  assert.equal(input.getAttribute('aria-label'), 'عنوان');
});

test('removes the real name from attributes set after the page loaded', async () => {
  const document = await page('<input id="existing">');
  const added = document.createElement('input');

  document.getElementById('existing').setAttribute('placeholder', mark('نام', 'name'));
  added.setAttribute('placeholder', mark('ایمیل', 'email'));
  document.body.append(added);
  await settle();

  assert.equal(document.getElementById('existing').getAttribute('placeholder'), 'نام');
  assert.equal(added.getAttribute('placeholder'), 'ایمیل');
});

test('updates the subtitle when the label text changes', async () => {
  const document = await page(`<label>${mark('عنوان', 'title')}</label>`);
  const label = document.querySelector('label');

  label.firstChild.nodeValue = mark('نام', 'name');
  await settle();

  assert.equal(label.firstChild.nodeValue, 'نام');
  assert.equal(label.querySelectorAll('.ntl-subtitle').length, 1);
  assert.equal(label.querySelector('.ntl-subtitle').textContent, 'name');
});

test('removes the subtitle when a reused label gets text without a real name', async () => {
  const document = await page(`<label>${mark('عنوان', 'title')}</label>`);
  const label = document.querySelector('label');

  label.firstChild.nodeValue = 'Word Count';
  await settle();

  assert.equal(label.textContent, 'Word Count');
  assert.equal(label.querySelector('.ntl-subtitle'), null);
});

test('removes the subtitle when a reused label gets the same text without a real name', async () => {
  const document = await page(`<table><tr><th>${mark('Name', 'name')}</th></tr></table>`);
  const header = document.querySelector('th');

  header.firstChild.nodeValue = 'Name';
  await settle();

  assert.equal(header.textContent, 'Name');
  assert.equal(header.querySelector('.ntl-subtitle'), null);
});

test('keeps the subtitle of a label added after load, then drops it on a same-text rewrite', async () => {
  const document = await page('<table><tr><th></th></tr></table>');
  const header = document.querySelector('th');

  header.append(document.createTextNode(mark('Name', 'name')));
  await settle();

  assert.equal(header.firstChild.nodeValue, 'Name');
  assert.equal(header.querySelector('.ntl-subtitle').textContent, 'name');

  header.firstChild.nodeValue = 'Name';
  await settle();

  assert.equal(header.querySelector('.ntl-subtitle'), null);
});

test('removes the subtitle when its label text is removed', async () => {
  const document = await page(`<label>${mark('عنوان', 'title')}</label>`);
  const label = document.querySelector('label');

  label.firstChild.remove();
  await settle();

  assert.equal(label.querySelector('.ntl-subtitle'), null);
});

test('removes the real name from the browser tab title', async () => {
  const document = await page('', mark('مقاله‌ها', 'posts'));

  assert.equal(document.title, 'مقاله‌ها');
});
