(function() {
    const {SLUG, PREF, URL} = fcgbf_vars;
    const el = a => document.querySelector(a) || {'innerHTML': '', 'value': '', 'addEventListener': ()=>{}, 'setAttribute': ()=>{}};
    //const els = a => document.querySelectorAll(a);
    const val = a => el(a)?.value;

    const formatData = (heading, data) => {
        if (!data) { return '' }
    
        const commit = data.commit.commit;
        const committer = commit.committer;
    
        const doneLabel = data.extended_locally?.checked ? 'Last Checked' : 'Last Updated';
    
        const print = {
            "Commiter Date": committer.date,
            "Commiter Message": commit.message,
            "Commiter Name": committer.name,
            "Branch": data.name,
            [doneLabel]: new Date(data.extended_locally.date*1000).toISOString().split('.')[0]+'Z',
        };
    
        let result = `<h3>${heading}</h3><dl>`;
        Object.entries(print).forEach(([k, v]) => {
            result += `<dt>${k}</dt><dd>${v}</dd>`;
        });
        result += '</dl>';
    
        return result;    
    }

    const fetchData = action => {
        return async () => {
            const post_id = val(`#post_ID`);
            const nonce = val(`#${PREF}rest-nonce`);
            const url = `${URL}${post_id}/${action}`;
            const current_field = el(`.${PREF}current`);
            const checked_field = el(`.${PREF}checked`);
            const response_field = el(`.${PREF}response`);
            const highlight_element = el(`#${PREF}rep-install`);

            // loader
            const loader_add = field => field.innerHTML = `<span class="${PREF}loader"></span>`;
            //const loader_remove = () => els(`.${PREF}loader`).forEach(el => el.remove());
            loader_add(response_field);

            // process
            try {
                let getResponse = async () => {
                    return fetch(
                        url,
                        {
                            method: 'get',
                            headers: { 'X-WP-Nonce': nonce },
                        }
                    );
                };

                try {
                    const response = await getResponse();
                    const jsonData = await response.json();
                    if ( jsonData.extended_locally?.checked === true ) {
                        checked_field.innerHTML = formatData('Just Checked', jsonData);
                        if ( jsonData.extended_locally?.has_changes === true ) {
                            highlight_element.classList.add(`${PREF}update-available`);
                        }
                    } else if ( jsonData.extended_locally?.installed === true ) {
                        checked_field.innerHTML = '';
                        current_field.innerHTML = formatData('Just Updated', jsonData);
                        highlight_element.classList.remove(`${PREF}update-available`);
                    }
                    response_field.innerHTML = `<h3>Full responce:</h3><pre>${JSON.stringify(jsonData, null, 2)}</pre>`;
                } catch (error) {
                    const response = await getResponse();
                    const textData = await response.text();
                    response_field.innerHTML = textData ? `<h3>Error response:</h3><pre>${textData}</pre>` : `<h3>The response is empty</h3>`;
                }

            } catch (error) {
                response_field.innerText = `<h3>Fetch error:</h3><pre>${error.message}</pre>`;
            }
        };
    };

    const addToggleButton = input => {
        //if (input.value.trim() === '') { return }

        const buttonHTML = `
            <button type="button" class="button button-secondary wp-hide-pw" data-toggle="0" aria-label="Show password">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
            </button>
        `;

        const buttonElement = new DOMParser().parseFromString(buttonHTML, 'text/html').body.firstChild;
        input.parentNode.insertBefore(buttonElement, input);
        const iconElement = buttonElement.querySelector('.dashicons');

        buttonElement.addEventListener('click', () => {
            iconElement.classList.remove('dashicons-visibility', 'dashicons-hidden');
            if (input.type === 'password') {
                input.type = 'text';
                buttonElement.querySelector('.dashicons').classList.add('dashicons-hidden');
            } else {
                input.type = 'password';
                buttonElement.querySelector('.dashicons').classList.add('dashicons-visibility');
            }
        });
    };

    let a = setInterval(function() {
        const d = document;
        let b = d.readyState;
        if (b !== 'complete' && b !== 'interactive') {
            return;
        }

        clearInterval(a);
        a = null;

        // fetch events
        el('#fcgbf-rep-check').addEventListener('click', e => fetchData('check')());
        el('#fcgbf-rep-install').addEventListener('click', e => fetchData('install')());

        // limit status options
        // editor screen

        //el( `#minor-publishing` ).remove(); // doesn't submit without it
        el( `#publish` ).setAttribute( 'name', 'save' );
        el( `#publish` ).value = `Save`;

        // password field visibility toggle eye
        const passwordFields = document.querySelectorAll(`.${PREF}fields input[type=password]`);
        passwordFields.forEach( pw => addToggleButton(pw) );

    }, 300);
})();