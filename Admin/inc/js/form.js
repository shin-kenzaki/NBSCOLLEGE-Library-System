
    document.addEventListener("DOMContentLoaded", function() {
        let steps = document.querySelectorAll(".form-step");
        let nextBtns = document.querySelectorAll(".next-step");
        let prevBtns = document.querySelectorAll(".prev-step");
        let currentStep = 0;

        function showStep(step) {
            steps.forEach((el, index) => {
                el.classList.toggle("d-none", index !== step);
            });
        }

        nextBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                if (currentStep < steps.length - 1) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
        });

        prevBtns.forEach(btn => {
            btn.addEventListener("click", () => {
                if (currentStep > 0) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
        });
    });
