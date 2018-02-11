try {
  document.getElementById('addOnScript').innerHTML = 'Session.set("AddOnInstalled", true)';
} catch (err) {
  console.error(err);
}
