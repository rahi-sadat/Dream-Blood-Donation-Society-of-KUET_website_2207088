


const homePage = document.getElementById('home-page');
const aboutPage = document.getElementById('about-page');
const navLinks = document.querySelectorAll('.nav-links a');

// Page Switching Logic
function showPage(pageId) {
    // Hide all pages
    homePage.style.display = 'none';
    aboutPage.style.display = 'none';

    
    if (pageId === 'about') {
        aboutPage.style.display = 'block';
    } else {
        homePage.style.display = 'block';
    }
    
    window.scrollTo(0, 0); 
}

// 3. Navigation Event Listeners
navLinks.forEach(link => {
    link.addEventListener('click', (e) => {
        const target = link.getAttribute('href');
        
        if (target === '#about') {
            e.preventDefault();
            showPage('about');
        } else if (target === '#home') {
            e.preventDefault();
            showPage('home');
        }
    });
});


const learnMoreBtn = document.querySelector('.btn-learn-more');


if (learnMoreBtn) {
    learnMoreBtn.addEventListener('click', (e) => {
        e.preventDefault();
        showPage('about'); 
    });
}