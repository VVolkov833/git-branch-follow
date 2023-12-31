(function() {
    const {SLUG, PREF, URL} = fcgbf_vars;
    const el = a => document.querySelector(a);
    const val = a => el(a)?.value;
    const print_result = result => {
        el(`.${PREF}updated`).innerHTML = result;
    };

    const formatData = (heading, data) => {
        if (!data) {
            return '';
        }
    
        const commit = data.commit.commit;
        const committer = commit.committer;
    
        const doneLabel = data.extended_locally.checked ? 'Last Checked' : 'Last Updated';
    
        const formatDate = (timestamp) => {
            const date = new Date(timestamp * 1000);
            return new Intl.DateTimeFormat('en-US', { dateStyle: 'full', timeStyle: 'long' }).format(date);
        };
    
        const print = {
            [doneLabel]: formatDate(data.extended_locally.date),
            "Commiter Date": formatDate(Date.parse(committer.date)),
            "Commiter Name": committer.name,
            "Commiter Message": commit.message,
            "Branch": data.name,
        };
    
        const container = document.createElement('div');
        const headingElement = document.createElement('h3');
        headingElement.textContent = heading;
        container.appendChild(headingElement);
    
        const dlElement = document.createElement('dl');
        Object.entries(print).forEach(([k, v]) => {
            const dtElement = document.createElement('dt');
            dtElement.textContent = k;
            dlElement.appendChild(dtElement);
    
            const ddElement = document.createElement('dd');
            ddElement.textContent = v;
            dlElement.appendChild(ddElement);
        });
        container.appendChild(dlElement);
    
        return container;
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
                        checked_field.innerHTML = '';
                        checked_field.append( formatData('Just Checked', jsonData) );
                    } else if ( jsonData.extended_locally?.installed === true ) {
                        checked_field.innerHTML = '';
                        current_field.innerHTML = '';
                        current_field.append( formatData('Just Updated', jsonData) );
                    }
                    response_field.innerHTML = `<h3>Full responce:</h3><pre>${JSON.stringify(jsonData, null, 2)}</pre>`;
                } catch (error) {
                    const response = await getResponse();
                    const textData = await response.text();
                    response_field.innerHTML = textData ? `<h3>Error response:</h3><pre>${textData}</pre>` : `<h3>The response is empty</h3>`;
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
                response_field.innerText = `<h3>Fetch error:</h3><pre>${error.message}</pre>`;
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