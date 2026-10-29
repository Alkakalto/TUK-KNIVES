'use strict';
const form = document.querySelector('#custom-request');
form?.addEventListener('submit', event => {
  event.preventDefault();
  const data = new FormData(form);
  const body = `Hej TUK-KNIVES!\n\nJag är intresserad av en specialbeställd kniv.\n\nNamn: ${data.get('name')}\nE-post: ${data.get('email')}\n\nMin idé:\n${data.get('message')}\n\nJag önskar återkoppling om utförande, pris och leveranstid.`;
  window.location.href = `mailto:tuk.knives@outlook.com?subject=${encodeURIComponent('Förfrågan om specialbeställd kniv')}&body=${encodeURIComponent(body)}`;
  document.querySelector('#custom-status').textContent = 'Ett mejlutkast har begärts i ditt e-postprogram. Kontrollera uppgifterna och skicka mejlet där. Om inget öppnas kan du mejla oss direkt på tuk.knives@outlook.com. Inget har skickats från webbplatsen.';
});
