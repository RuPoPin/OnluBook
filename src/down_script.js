document.addEventListener('DOMContentLoaded', function() {
    const newsSlider = document.querySelector('.news-slider'); 

    if (!newsSlider) {
        return; 
    }

    const slidesContainer = newsSlider.querySelector('.slides');
    const slides = newsSlider.querySelectorAll('.slide'); 
    const prevButton = newsSlider.querySelector('.prev');
    const nextButton = newsSlider.querySelector('.next');

    if (!slidesContainer || slides.length === 0 || !prevButton || !nextButton) {

        return; 
    }

    let currentSlideIndex = 0;
    const totalSlides = slides.length;
    let slidesToShow = 2;
    const firstSlideStyle = window.getComputedStyle(slides[0]);
    const flexBasis = firstSlideStyle.getPropertyValue('flex-basis');
    if (flexBasis && flexBasis.endsWith('%')) {
        const basisPercentage = parseFloat(flexBasis);
        if (basisPercentage > 0) {
            slidesToShow = Math.round(100 / basisPercentage);
        }
    }
    slidesToShow = Math.min(slidesToShow, totalSlides);


    function updateSlider() {
        const slideWidthPercentage = 100 / slidesToShow;
        slidesContainer.style.transform = `translateX(-${currentSlideIndex * slideWidthPercentage}%)`;

        slides.forEach((slide, index) => {
            slide.classList.remove('active');
            if (index >= currentSlideIndex && index < currentSlideIndex + slidesToShow) {
                slide.classList.add('active');
            }
        });
        prevButton.disabled = currentSlideIndex === 0;
        nextButton.disabled = currentSlideIndex >= totalSlides - slidesToShow;
    }
    function changeSlide(direction) {
        const newSlideIndex = currentSlideIndex + direction;

        if (newSlideIndex < 0) {
            currentSlideIndex = 0; 
        } else if (newSlideIndex > totalSlides - slidesToShow) {
            currentSlideIndex = totalSlides - slidesToShow;
        } else {
            currentSlideIndex = newSlideIndex;
        }
        if (totalSlides <= slidesToShow) {
            currentSlideIndex = 0;
        }

        updateSlider();
    }

    if (totalSlides > 0) {
        updateSlider(); 
    } else {
        prevButton.style.display = 'none';
        nextButton.style.display = 'none';
    }

    if (prevButton.getAttribute('onclick') && nextButton.getAttribute('onclick')) {
        window.changeSlide = changeSlide; // Делаем функцию глобально доступной
    } else {
        prevButton.addEventListener('click', () => changeSlide(-1));
        nextButton.addEventListener('click', () => changeSlide(1));
    }
});