(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        const recaptcha = document.querySelectorAll(".g-recaptcha");

        for (let index = 0; index < recaptcha.length; index++) {
            const recap = recaptcha[index];
            const recaptchaType = recap?.getAttribute('data-size') || null;
            if (recaptchaType !== 'invisible') return;

            document.body.append(recap);
        };
     })

})()
