(function() {
    const {SLUG, PREF, URL} = fcgbf_vars;
    const el = a => document.querySelector(a);
    const val = a => el(a)?.value;
    const print_result = result => {
        el(`.${PREF}updated`).innerHTML = result;
    };

    const fetch_data = action => {
        return async () => {
            const post_id = val(`#post_ID`);
            const nonce = val(`#${PREF}rest-nonce`);
            const url = `${URL}${post_id}/${action}`;
            const response_field = el(`.${PREF}response`)
            const checked_field = el(`.${PREF}checked`)

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
                    let response = await getResponse();
                    let jsonData = await response.json()
                    response_field.innerHTML = `<h3>Full responce:</h3><pre>${JSON.stringify(jsonData, null, 2)}</pre>`;
                } catch (error) {
                    let response = await getResponse();
                    const textData = await response.text()
                    response_field.innerHTML = textData && `<h3>Error response:</h3><pre>${textData}</pre>`;
                }
//*/
/*
                if (response.status === 200) {
                    checked_field.innerHTML = `
                    <h3>Fetched:</h3>
                    <dl>
                        <dt>Commiter Date</dt>
                        <dd>${jsonData?.commit?.commit?.committer?.date}</dd>
                        <dt>Commit Message</dt>
                        <dd>${jsonData?.commit?.commit?.message}</dd>
                        <dt>Commiter Name</dt>
                        <dd>${jsonData?.commit?.commit?.committer?.name}</dd>
                        <dt>Branch</dt>
                        <dd>${jsonData?.name}</dd>
                    </dl>`;
                }
//*/
            } catch (error) {
                response_field.innerText = `Fetch error: ${error.message}`;
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

// wrapper
// wait for dom
    // assign events
// fetching functions
/*
!function(){let a=setInterval(function(){const d=document;let b=d.readyState;if(b!=='complete'&&b!=='interactive'){return}clearInterval(a);a=null;
    const {SLUG, PREF, URL} = fcgbf_vars;

    const print_result = result => {
        d.querySelector(`.${PREF}updated`).innerHTML = `<pre>${result}</pre>`;
    };

    const fetch_data = async () => {
        const val = a => d.querySelector(a)?.value;
        const post_id = val(`#post_ID`);
        const nonce = val(`#${PREF}rest-nonce`);
        const url = URL+post_id;

        return await fetch(
            url,
            {
                method: 'get',
                headers: { 'X-WP-Nonce' : nonce },
            }
        )
        .then( response => response.status === 200 && response.json() || [] )
        .then( data => print_result(data) )
        .catch( error => print_result(error) );

    };

    fetch_data();

}, 300 )}();
//*/