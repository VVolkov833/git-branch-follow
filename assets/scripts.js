!function(){let a=setInterval(function(){const d=document;let b=d.readyState;if(b!=='complete'&&b!=='interactive'){return}clearInterval(a);a=null;
    const {SLUG, PREF, URL} = fcgbf_vars;

    const fetch_data = async () => {
        const val = a => document.querySelector(a)?.value;
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
        .then( data => data?.filter( el => el.id !== post_id ) || [] )
        .catch( error => [] );

    };

    console.log(fetch_data());

}, 300 )}();