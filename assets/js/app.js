const yearSpan = document.getElementById("year");
const welcomeBtn = document.getElementById("welcomeBtn");
const communityBtn = document.getElementById("communityBtn");

if (yearSpan) {
  yearSpan.textContent = new Date().getFullYear();
}

if (welcomeBtn) {
  welcomeBtn.addEventListener("click", () => {
    alert("Bienvenido a Retro Vibes. La nostalgia gamer comienza aquí.");
  });
}

if (communityBtn) {
  communityBtn.addEventListener("click", () => {
    alert("Muy pronto podrás comentar y compartir tus recuerdos retro.");
  });
}