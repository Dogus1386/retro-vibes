// 1) "Capturamos" (buscamos) elementos HTML por su id
const gameList = document.getElementById("gameList");
const gameInput = document.getElementById("gameInput");
const addBtn = document.getElementById("addBtn");

// 2) Creamos una lista inicial en memoria (esto todavía NO es base de datos)
const games = ["Pac-Man", "Contra", "Donkey Kong", "Street Fighter", "Metal Slug"];


// 3) Función para dibujar la lista en el HTML
function renderGames() {
  // Limpia lo anterior
  gameList.innerHTML = "";

  // Recorre el arreglo "games" y crea <li> por cada juego
  games.forEach((name, index) => {
    const li = document.createElement("li");
    li.textContent = name;

    // Botón eliminar
    const delBtn = document.createElement("button");
    delBtn.textContent = "Eliminar";

    // Evento click para eliminar ese juego
    delBtn.addEventListener("click", () => {
      games.splice(index, 1);   // quita 1 elemento en la posición index
      renderGames();            // vuelve a dibujar
    });

    // Para poner el botón a la derecha, metemos li + botón
    li.appendChild(delBtn);
    gameList.appendChild(li);
  });
}

// 4) Evento: cuando doy click en "Agregar"
addBtn.addEventListener("click", () => {
  const value = gameInput.value.trim();

  if (value === "") return; // si está vacío, no hacemos nada

  games.push(value);        // agrega al arreglo
  gameInput.value = "";     // limpia el input
  renderGames();            // redibuja
});

// 5) Dibujamos por primera vez al cargar la página
renderGames();