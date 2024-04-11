function setTime(){
    let time = new Date().toLocaleTimeString();
    let time_placeholder = document.getElementById('time');

    time_placeholder.textContent = time;
}

window.addEventListener('load', function () {
    setTime();
});

setInterval(() => setTime(),1000);