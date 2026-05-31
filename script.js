
console.log("Script loaded successfully!");

// Main page references: used by the single-page Home/About/Campaigns sections.
const homePage = document.getElementById('home-page');
const aboutPage = document.getElementById('about-page');
const campaignPage = document.getElementById('campaign-content');
const navLinks = document.querySelectorAll('.nav-links a');
const linkButtons = document.querySelectorAll('[data-link]');

// Profile sidebar references: used to open and close the account drawer.
const profileMenuToggle = document.querySelector('[data-profile-menu-toggle]');
const profileMenuCloseButtons = document.querySelectorAll('[data-profile-menu-close]');


// Page switcher: hides and shows the homepage, about page, or campaigns content.
async function showPage(pageId) {
    if (!homePage || !aboutPage || !campaignPage) return;
   
    homePage.style.display = 'none';
    aboutPage.style.display = 'none';
    campaignPage.style.display = 'none';

    
    if (pageId === 'about') {
        aboutPage.style.display = 'block';
    }
    else if (pageId === 'campaigns') {
        
        try {
            const response = await fetch('campaigns.html');
            if (!response.ok) throw new Error('Campaign file not found');
            const data = await response.text();
            
            campaignPage.innerHTML = data;
            campaignPage.style.display = 'block';
            initCampaignSliders();
        } catch (error) {
            console.error("Error loading campaigns:", error);
            homePage.style.display = 'block'; 
        }
    } else {
        homePage.style.display = 'block';
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}


navLinks.forEach(link => {
    link.addEventListener('click', async (e) => {
       const href = link.getAttribute('href');
        console.log("Link clicked:", href); 
        
      
        if (href.startsWith('#')) {
            e.preventDefault();
            
           
            const target = href.substring(1); 
            
            console.log("Clean target name:", target);
         
        
        if (target === 'about') {
            showPage('about');
        } else if (target === 'home') {
            showPage('home');
        }
        else if (target === 'campaigns') {
            await showPage('campaigns');
        }
    }
    });
});

linkButtons.forEach((button) => {
    button.addEventListener('click', () => {
        window.location.href = button.dataset.link;
    });
});

// Profile sidebar behavior: opens from the avatar/name and closes with backdrop or Escape.
if (profileMenuToggle) {
    profileMenuToggle.addEventListener('click', () => {
        document.body.classList.toggle('profile-menu-open');
    });
}

profileMenuCloseButtons.forEach((button) => {
    button.addEventListener('click', () => {
        document.body.classList.remove('profile-menu-open');
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        document.body.classList.remove('profile-menu-open');
    }
});

const initialTarget = window.location.hash.substring(1);

if (initialTarget === 'about' || initialTarget === 'campaigns') {
    showPage(initialTarget);
}


const learnMoreBtn = document.querySelector('.btn-learn-more');


if (learnMoreBtn) {
    learnMoreBtn.addEventListener('click', (e) => {
        e.preventDefault();
        showPage('about'); 
    });
}

function initCampaignSliders() {
    document.querySelectorAll('[data-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-slider-track]');
        const prevBtn = slider.querySelector('[data-slider-prev]');
        const nextBtn = slider.querySelector('[data-slider-next]');
        const count = slider.querySelector('[data-slider-count]');
        const dotsWrap = slider.querySelector('[data-slider-dots]');
        let currentIndex = 0;

        if (!track || !prevBtn || !nextBtn || !count || !dotsWrap) return;

        const slides = Array.from(track.querySelectorAll('img'));

        if (slides.length === 0) return;

        dotsWrap.innerHTML = '';
        slides.forEach((_, index) => {
            const dot = document.createElement('button');
            dot.type = 'button';
            dot.className = 'slider-dot';
            dot.setAttribute('aria-label', `Show campaign photo ${index + 1}`);
            dot.addEventListener('click', () => updateSlider(index));
            dotsWrap.appendChild(dot);
        });

        const dots = Array.from(dotsWrap.querySelectorAll('.slider-dot'));

        function updateSlider(index) {
            currentIndex = (index + slides.length) % slides.length;
            track.style.transform = `translateX(-${currentIndex * 100}%)`;
            count.textContent = `${currentIndex + 1} / ${slides.length}`;
            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle('active', dotIndex === currentIndex);
            });
        }

        prevBtn.addEventListener('click', () => updateSlider(currentIndex - 1));
        nextBtn.addEventListener('click', () => updateSlider(currentIndex + 1));
        updateSlider(0);
    });
}

// Blood request form validation: checks date and Bangladeshi phone format before submit.
const requestForm = document.querySelector('[data-request-form]');

if (requestForm) {
    const dateInput = requestForm.querySelector('#needed_date');
    const phoneInput = requestForm.querySelector('#contact_phone');
    const today = new Date().toISOString().split('T')[0];

    if (dateInput) {
        dateInput.min = today;
    }

    requestForm.addEventListener('submit', (event) => {
        if (dateInput && dateInput.value < today) {
            event.preventDefault();
            alert('Please select today or a future date for the blood request.');
            dateInput.focus();
            return;
        }

        if (phoneInput && !/^01[0-9]{9}$/.test(phoneInput.value.trim())) {
            event.preventDefault();
            alert('Please enter a valid Bangladeshi mobile number, like 01XXXXXXXXX.');
            phoneInput.focus();
        }
    });
}
