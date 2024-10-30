
function getLocation(input) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            showPosition(position, input);
        });
    } else {
        x.innerHTML = "Geolocation is not supported by this browser.";
    }
}

function showPosition(position, input) {
    console.log(position, input);
    let request = new XMLHttpRequest();
    request.open('GET', `https://maps.googleapis.com/maps/api/geocode/json?latlng=${position.coords.latitude},${position.coords.longitude}&key=${lbecGoogleApiKey}&sensor=true`, true);
    request.onreadystatechange = function() {
        if(request.readyState === 4 && request.status === 200){
            console.log(request);
            var data = JSON.parse(request.responseText);
            var address = data.results[0];
            input.value = address.formatted_address;
        }
    }
    request.send();
}

document.addEventListener('DOMContentLoaded', (event) => {
    let cityInput = document.getElementById('city');

    if (cityInput && !cityInput.value.length) {
        getLocation(cityInput);
    }
})
