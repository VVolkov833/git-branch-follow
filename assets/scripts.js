(function() {
    const {SLUG, PREF, URL} = fcgbf_vars;
    const el = a => document.querySelector(a);
    const val = a => el(a)?.value;

    const formatData = (heading, data) => {
        if (!data) { return '' }
    
        const commit = data.commit.commit;
        const committer = commit.committer;
    
        const doneLabel = data.extended_locally?.checked ? 'Last Checked' : 'Last Updated';
    
        const print = {
            [doneLabel]: new Date(data.extended_locally.date*1000).toISOString().split('.')[0]+'Z',
            "Commiter Date": committer.date,
            "Commiter Name": committer.name,
            "Commiter Message": commit.message,
            "Branch": data.name,
        };
    
        let result = `<h3>${heading}</h3><dl>`;
        Object.entries(print).forEach(([k, v]) => {
            result += `<dt>${k}</dt><dd>${v}</dd>`;
        });
        result += '</dl>';
    
        return result;    
    }

    const fetch_data = action => {
        return async () => {
            const post_id = val(`#post_ID`);
            const nonce = val(`#${PREF}rest-nonce`);
            const url = `${URL}${post_id}/${action}`;
            const current_field = el(`.${PREF}current`);
            const checked_field = el(`.${PREF}checked`);
            const response_field = el(`.${PREF}response`);

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
                        checked_field?.innerHTML = formatData('Just Checked', jsonData);
                    } else if ( jsonData.extended_locally?.installed === true ) {
                        checked_field?.innerHTML = '';
                        current_field?.innerHTML = formatData('Just Updated', jsonData);
                    }
                    response_field?.innerHTML = `<h3>Full responce:</h3><pre>${JSON.stringify(jsonData, null, 2)}</pre>`;
                } catch (error) {
                    const response = await getResponse();
                    const textData = await response.text();
                    response_field?.innerHTML = textData ? `<h3>Error response:</h3><pre>${textData}</pre>` : `<h3>The response is empty</h3>`;
                }

            } catch (error) {
                response_field?.innerText = `<h3>Fetch error:</h3><pre>${error.message}</pre>`;
            }
        };
    };


    let a = setInterval(function() {
        const d = document;
        let b = d.readyState;
        if (b !== 'complete' && b !== 'interactive') {
            return;
        }

        clearInterval(a);
        a = null;

        el('#fcgbf-rep-check').addEventListener('click', e => fetch_data('check')());
        el('#fcgbf-rep-install').addEventListener('click', e => fetch_data('install')());
    }, 300);
})();