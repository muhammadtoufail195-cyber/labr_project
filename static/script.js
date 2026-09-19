const pullString = document.getElementById('pullString');
const body = document.body;

let isPulled = false;

// Pull/Click Switch Toggle Function
pullString.addEventListener('click', () => {
    // Pull Down Animation
    pullString.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        pullString.style.transform = 'translateY(0px)';
    }, 200);

    // Toggle Light and Login Form
    isPulled = !isPulled;
    if (isPulled) {
        body.classList.add('light-active');
    } else {
        body.classList.remove('light-active');
    }
});
