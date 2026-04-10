function formatTime12hAmPm(date, timeZone) {
    let parts = new Intl.DateTimeFormat('en-US', {
        timeZone: timeZone,
        hour: 'numeric',
        minute: '2-digit',
        second: '2-digit',
        hour12: false
    }).formatToParts(date);
    let hourPart = null;
    let minutePart = null;
    let secondPart = null;
    for (let i = 0; i < parts.length; i++) {
        let p = parts[i];
        if (p.type === 'hour') hourPart = p;
        if (p.type === 'minute') minutePart = p;
        if (p.type === 'second') secondPart = p;
    }
    if (!hourPart || !minutePart) {
        return date.toLocaleString('en-US', {
            timeZone: timeZone,
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }
    let hour24 = parseInt(hourPart.value, 10);
    if (isNaN(hour24)) {
        return date.toLocaleString('en-US', {
            timeZone: timeZone,
            hour: 'numeric',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
    }
    let minute = minutePart.value;
    let second = secondPart ? secondPart.value : '00';
    let hour12 = hour24 % 12;
    if (hour12 === 0) {
        hour12 = 12;
    }
    let ampm = hour24 < 12 ? 'AM' : 'PM';
    return hour12 + ':' + minute + ':' + second + ' ' + ampm;
}

function setTime(){
    let tz = window.teeTimeDisplayTimezone || 'Europe/London';
    let time = formatTime12hAmPm(new Date(), tz);
    let time_placeholder = document.getElementById('time');
    if (time_placeholder) {
        time_placeholder.textContent = time;
    }
}

window.addEventListener('load', function () {
    setTime();
});

setInterval(() => setTime(),1000);