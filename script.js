// Local PHP server command: D:\xampp\php\php.exe -S localhost:8000
console.log("Script loaded successfully!");

// Main page references: used by the single-page Home/About/Campaigns sections.
const homePage = document.getElementById('home-page');
const aboutPage = document.getElementById('about-page');
const campaignPage = document.getElementById('campaign-content');
const navLinks = document.querySelectorAll('.nav-links a');
const pageLinks = document.querySelectorAll('.nav-links a, .main-footer a');
const linkButtons = document.querySelectorAll('[data-link]');
const bloodAnswerButtons = document.querySelectorAll('[data-blood-answer]');
const bloodAnswerPanel = document.querySelector('[data-blood-answer-panel]');
const bloodAnswerTitle = document.querySelector('[data-blood-answer-title]');
const bloodAnswerText = document.querySelector('[data-blood-answer-text]');
let campaignContentCache = '';
let campaignContentPromise = null;

// Profile sidebar references: used to open and close the account drawer.
const profileMenuToggle = document.querySelector('[data-profile-menu-toggle]');
const profileMenuCloseButtons = document.querySelectorAll('[data-profile-menu-close]');


// Page switcher: hides and shows the homepage, about page, or campaigns content.
async function showPage(pageId) {
    if (!homePage || !aboutPage || !campaignPage) return;
    resetBloodAnswerPanel();
    setActiveNavLink(pageId);
   
    homePage.style.display = 'none';
    aboutPage.style.display = 'none';
    campaignPage.style.display = 'none';

    
    if (pageId === 'about') {
        aboutPage.style.display = 'block';
    }
    else if (pageId === 'campaigns') {
        
        try {
            const data = await loadCampaignContent();
            if (campaignPage.innerHTML !== data) {
                campaignPage.innerHTML = data;
            }
            campaignPage.style.display = 'block';
            initCampaignSliders();
        } catch (error) {
            console.error("Error loading campaigns:", error);
            setActiveNavLink('home');
            homePage.style.display = 'block'; 
        }
    } else {
        homePage.style.display = 'block';
    }
    
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function setActiveNavLink(pageId) {
    const activePage = pageId === 'campaigns' ? 'campaigns' : pageId === 'about' ? 'about' : 'home';

    navLinks.forEach((link) => {
        const href = link.getAttribute('href');
        link.classList.toggle('active-link', href === `#${activePage}`);
    });
}

function loadCampaignContent() {
    if (campaignContentCache) {
        return Promise.resolve(campaignContentCache);
    }

    if (!campaignContentPromise) {
        campaignContentPromise = fetch('campaigns.html')
            .then((response) => {
                if (!response.ok) throw new Error('Campaign file not found');
                return response.text();
            })
            .then((data) => {
                campaignContentCache = data;
                return data;
            })
            .catch((error) => {
                campaignContentPromise = null;
                throw error;
            });
    }

    return campaignContentPromise;
}

if (campaignPage) {
    loadCampaignContent().catch((error) => {
        console.error("Error preloading campaigns:", error);
    });
}


pageLinks.forEach(link => {
    link.addEventListener('click', async (e) => {
       const href = link.getAttribute('href');
        console.log("Link clicked:", href); 
        
      
        if (href && href.startsWith('#')) {
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

const bloodAnswers = {
    what: {
        title: 'What is blood?',
        text: 'Blood carries oxygen, nutrients, hormones, and immune cells throughout the body. It also helps control bleeding and supports recovery during illness, surgery, and emergencies.'
    },
    donate: {
        title: 'Who can donate?',
        text: 'A healthy adult can usually donate if they meet age, weight, hemoglobin, and medical safety requirements. Anyone who is ill, recently had major treatment, or is unsure should consult a doctor first.'
    },
    groups: {
        title: 'Blood Groups',
        text: 'The main blood groups are A, B, AB, and O, each with positive or negative Rh type. Matching the correct group is important for safe transfusion.'
    },
    faqs: {
        title: 'FAQs',
        text: 'Blood donation is normally quick and safe when done through trained medical staff. Donors should eat well, drink water, rest after donation, and follow the recommended gap before donating again.'
    }
};

function resetBloodAnswerPanel() {
    if (bloodAnswerPanel) {
        bloodAnswerPanel.hidden = true;
    }

    bloodAnswerButtons.forEach((button) => {
        button.classList.remove('active');
    });
}

bloodAnswerButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const answer = bloodAnswers[button.dataset.bloodAnswer];
        if (!answer || !bloodAnswerPanel || !bloodAnswerTitle || !bloodAnswerText) return;

        if (button.classList.contains('active') && !bloodAnswerPanel.hidden) {
            resetBloodAnswerPanel();
            return;
        }

        bloodAnswerTitle.textContent = answer.title;
        bloodAnswerText.textContent = answer.text;
        bloodAnswerPanel.hidden = false;

        bloodAnswerButtons.forEach((item) => {
            item.classList.toggle('active', item === button);
        });
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
} else if (homePage && aboutPage && campaignPage) {
    setActiveNavLink('home');
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

// Donate action confirmation: the server still performs the real eligibility checks.
document.querySelectorAll('[data-donate-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!confirm('Send your donor response for this blood request?')) {
            event.preventDefault();
        }
    });
});
