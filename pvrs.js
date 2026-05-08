const landingPage = document.getElementById("landing-page");
const registrationSystem = document.getElementById("registration-system");
const startBtn = document.getElementById("start-btn");

// 2. Get Form Steps & Navigation Buttons
const steps = document.querySelectorAll(".form-step");
const nextBtns = document.querySelectorAll(".next-btn");
const prevBtns = document.querySelectorAll(".prev-btn");

let currentStep = 0;

// Landing Page Button Logic
startBtn.addEventListener("click", () => {
    landingPage.style.display = "none";
    registrationSystem.style.display = "block";
    updateStepper(); // Call this to set the very first state!
});

// Helper Function: Update the Stepper Pill States
function updateStepper() {
    const stepContainers = document.querySelectorAll(".stepper-pill .step");
    stepContainers.forEach((step, index) => {
        const circle = step.querySelector(".step-circle");
        
        // Remove old classes first
        circle.classList.remove("active", "completed", "inactive");
        if (index < currentStep) {
            circle.classList.add("completed"); // This is a PAST page -> Add Checkmark
        } 
        else if (index === currentStep) {
            circle.classList.add("active"); // This is the CURRENT page -> Show Waving Dots
        } 
        else {
            circle.classList.add("inactive");  // This is a FUTURE page -> Show Static Dots
        }
    });
}

// Next Button Function
nextBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
        if (currentStep < steps.length - 1) {
            steps[currentStep].classList.remove("step-active");
            currentStep++;
            steps[currentStep].classList.add("step-active");
            updateStepper(); // Update dots
        }
    });
});

// Back Button Function
prevBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
        if (currentStep > 0) {
            steps[currentStep].classList.remove("step-active");
            currentStep--;
            steps[currentStep].classList.add("step-active");
            updateStepper(); // Update dots
        }
    });
});