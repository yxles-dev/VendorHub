const landingPage = document.getElementById("landing-page");
const registrationSystem = document.getElementById("registration-system");
const startBtn = document.getElementById("start-btn");

// 2. Get Form Steps & Navigation Buttons
const steps = document.querySelectorAll(".form-step");
const nextBtns = document.querySelectorAll(".next-btn");
const prevBtns = document.querySelectorAll(".prev-btn");

let currentStep = 0;

startBtn.addEventListener("click", () => {
    landingPage.style.display = "none";
    registrationSystem.style.display = "block";
    updateStepper(); // Call this to set the very first state!
    updateNextButtonState(); // Initialize button state
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

// check if all forms are filled up before proceeding to next page
function isStepValid(stepIndex) {
    const step = steps[stepIndex];
    const inputs = step.querySelectorAll("input, select");
    const checkedRadioGroups = new Set();
    
    for (let input of inputs) {
        const inputType = input.type;

        if (input.id === 'contact-number' || input.id === 'emergency-contact') {
            input.classList.remove('invalid-phone');
        }

        // Check all inputs
        if (["text", "email", "tel", "date", "file"].includes(inputType)) {
            if (input.hasAttribute("required") && !input.value.trim()) return false;

            // check if the inputted phone number has 11 digits
            if ((input.id === 'contact-number' || input.id === 'emergency-contact') && input.value.trim()) {
                const digits = input.value.replace(/\D/g, '');
                if (digits.length !== 11) {
                    input.classList.add('invalid-phone');
                    return false;
                }
            }
        }
        // Check select dropdowns
        else if (input.tagName === "SELECT") {
            if (input.hasAttribute("required") && !input.value) return false;
        }
        // Check radio buttons
        else if (inputType === "radio") {
            const radioName = input.name;
            if (!checkedRadioGroups.has(radioName)) {
                const radioGroup = step.querySelectorAll(`input[name="${radioName}"]`);
                const isChecked = Array.from(radioGroup).some(radio => radio.checked);
                if (!isChecked) return false;
                checkedRadioGroups.add(radioName);
            }
        }
        // Check checkboxes
        else if (inputType === "checkbox") {
            if (!input.checked) return false;
        }
    }
    
    return true;
}

// enable next button
function updateNextButtonState() {
    const nextBtn = steps[currentStep].querySelector(".next-btn");
    if (nextBtn) {
        if (isStepValid(currentStep)) {
            nextBtn.disabled = false;
            nextBtn.style.opacity = "1";
            nextBtn.style.cursor = "pointer";
        } else {
            nextBtn.disabled = true;
            nextBtn.style.opacity = "0.5";
            nextBtn.style.cursor = "not-allowed";
        }
    }
}

// Add event listeners to all form inputs for real-time validation
const formInputs = document.querySelectorAll("#market-form input, #market-form select");
formInputs.forEach((input) => {
    input.addEventListener("change", updateNextButtonState);
    input.addEventListener("input", updateNextButtonState);
});

// Next Button Function
nextBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
        if (isStepValid(currentStep) && currentStep < steps.length - 1) {
            steps[currentStep].classList.remove("step-active");
            currentStep++;
            steps[currentStep].classList.add("step-active");
            updateStepper(); // Update dots
            updateNextButtonState(); // Update button state for new step
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