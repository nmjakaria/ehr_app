// assets/js/script.js

document.addEventListener('DOMContentLoaded', function() {
    // Example: Dynamic welcome message based on time (if needed on other pages)
    const welcomeMessageElement = document.getElementById('welcomeMessage'); // Assuming you'll have an element with this ID
    if (welcomeMessageElement) {
        const hour = new Date().getHours();
        let greeting;
        if (hour < 12) {
            greeting = "Good Morning";
        } else if (hour < 18) {
            greeting = "Good Afternoon";
        } else {
            greeting = "Good Evening";
        }
        // This will be useful on dashboard pages
        // welcomeMessageElement.textContent = greeting + ", " + welcomeMessageElement.textContent;
    }

    console.log("EHR App scripts loaded!");
});