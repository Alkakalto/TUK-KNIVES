'use strict';
const menu = document.querySelector('#navbar');
const toggle = document.querySelector('#menu-btn');
if (menu && toggle) {
 const close = () => { menu.classList.remove('active'); toggle.setAttribute('aria-expanded', 'false'); toggle.textContent = '☰'; };
 toggle.addEventListener('click', () => { const open = menu.classList.toggle('active'); toggle.setAttribute('aria-expanded', String(open)); toggle.textContent = open ? '✕' : '☰'; });
 document.addEventListener('click', ev => { if (!menu.contains(ev.target) && !toggle.contains(ev.target)) close(); });
 document.addEventListener('keydown', ev => { if (ev.key === 'Escape' && menu.classList.contains('active')) { close(); toggle.focus(); } });
}
document.querySelectorAll('[data-gallery]').forEach(link => link.addEventListener('click', ev => { ev.preventDefault(); const main = document.querySelector('#gallery-main'); main.src = link.href; main.alt = link.querySelector('img').alt; }));
const upload = document.querySelector('#image-upload');
let previewURLs = [];
upload?.addEventListener('change', () => {
 previewURLs.forEach(URL.revokeObjectURL); previewURLs = [];
 const holder = document.querySelector('#upload-preview'); holder.replaceChildren();
 for (const file of upload.files) {
  const figure = document.createElement('figure'); const caption = document.createElement('figcaption'); caption.textContent = file.name; figure.append(caption);
  if (['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) { const img = document.createElement('img'); const url = URL.createObjectURL(file); previewURLs.push(url); img.src = url; img.alt = file.name; figure.prepend(img); }
  holder.append(figure);
 }
});
const country = document.querySelector('[name="country"]');
const payment = document.querySelector('#payment-method');
if (country && payment) { const update = () => { payment.value = country.value.trim().toLocaleLowerCase('sv') === 'sverige' ? 'Swish' : 'Betalning enligt överenskommelse'; }; country.addEventListener('input', update); update(); }
// Feedback only: authorization, availability, prices and duplicate protection live on the server.
document.querySelectorAll('form[method="post"]').forEach(form => form.addEventListener('submit', () => { form.setAttribute('aria-busy','true'); }));
