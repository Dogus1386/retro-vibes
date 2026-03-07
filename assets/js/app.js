const yearSpan = document.getElementById("year");
const welcomeBtn = document.getElementById("welcomeBtn");
const communityBtn = document.getElementById("communityBtn");

yearSpan.textContent = new Date().getFullYear();

welcomeBtn.addEventListener("click", () => {
  alert("Bienvenido a Retro Vibes. La nostalgia gamer comienza aquí.");
});

communityBtn.addEventListener("click", () => {
  alert("Muy pronto podrás comentar y compartir tus recuerdos retro.");
});