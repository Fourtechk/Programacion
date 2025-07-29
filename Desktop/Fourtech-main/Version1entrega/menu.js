const toggleBtn = document.querySelector('.menu-toggle');
const closeBtn = document.querySelector('.close-menu');
const menu = document.getElementById('sideMenu');

toggleBtn.addEventListener('click', () => {
    menu.classList.toggle('show');
});

closeBtn.addEventListener('click', () => {
    menu.classList.remove('show');
});