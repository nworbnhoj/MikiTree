///////////////// Service Worker functions ///////////////////


// Registering Service Worker
if('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js');
        console.log("serviceWorker registered.");
    });
};


function getServiceWorker(){
    if(!'serviceWorker' in navigator){
        console.log("failed to get navigator.serviceWorker");
        return;
    }
    var worker = navigator.serviceWorker.controller;  
    if(!worker){
        console.log("failed to get navigator.serviceWorker.controller");
        return;
    }
    return worker;
}  

///////////////// Event functions ///////////////////

function onload() {
    if (show_get().indexOf('p') > -1) {
        load_thumbs();
        align_thumbs();
    }
    pack();
}

function load_profile(event) {
    event.cancelBubble = true;
    var target = event.currentTarget;
    load(target.getAttribute('key'), target);
}

function load_new(event) {
    event.cancelBubble = true;
    var form = event.target.closest('form');
    var key_input = form.elements.namedItem('key');
    if (key_input) {
        load(key_input.value, form.parentElement);
    }
}

function load(key, target) {
    if (!key) {
        alert("Sorry the WikiTree-ID is missing for this Profile for some reason.");
        return;
    }
    target.classList.add('spin');
    var show = show_get().replace('r', ''); // special case: prevent 'r'  propogating from help
    var params = {
        'key': key,
        'depth': depth_get(),
        'show': show,
    };
    var esc = encodeURIComponent;
    var query = Object.keys(params)
        .map(k => esc(k) + '=' + esc(params[k]))
        .join('&');
    window.location.href = "index.php?" + query;
    setTimeout(unspin, 5000, target);
}


function unspin(element) {
    element.classList.remove('spin');
    element.firstElementChild.classList.remove('hide');
}

function search_show(event) {
    event.cancelBubble = true;
    event.target.classList.toggle('checked');
    document.getElementById('search_div').classList.toggle('hide');
}

function search_change(event) {
    var child_first = document.getElementById('child_first');
    var child_last = document.getElementById('child_last');
    var search_button = document.getElementById('search_button');
    if (child_first.value.length + child_last.value.length == 0) {
        child_first.classList.add('hilite');
        child_last.classList.add('hilite');
        search_button.disabled = true;
        search_button.classList.remove('hilite');
    } else {
        child_first.classList.remove('hilite');
        child_last.classList.remove('hilite');
        search_button.disabled = false;
        search_button.classList.add('hilite');
    }
}

function settings(event) {
    event.cancelBubble = true;
    event.target.classList.toggle('checked');
    document.getElementById('settings').classList.toggle('hide');
}

function depth_changed(event) {
    event.cancelBubble = true;
    var get = document.getElementById('get');
    var data_depth = get.getAttribute('depth');
    var depth = depth_get();
    if (depth > data_depth) {
        get.setAttribute('depth', depth);
        load_profile(event);
    } else {
        resize(event);
    }
}

function show_changed(event) {
    event.cancelBubble = true;
    switch (event.target.value) {
        case 'f':
            document.querySelector("input[name='show'][value='F']").checked = false;
            break;
        case 'F':
            document.querySelector("input[name='show'][value='f']").checked = false;
            break;
        case 'm':
            document.querySelector("input[name='show'][value='M']").checked = false;
            break;
        case 'M':
            document.querySelector("input[name='show'][value='m']").checked = false;
            break;
        case 'l':
            document.querySelector("input[name='show'][value='L']").checked = false;
            break;
        case 'L':
            document.querySelector("input[name='show'][value='l']").checked = false;
            break;
        case 'p':
            if (document.querySelector("input[name='show'][value='p']").checked) {
                load_thumbs();
            }
            align_thumbs();
            break;
    }
    show_sort();
    pack();
}

function align_thumbs() {
    var checked = document.querySelector("input[name='show'][value='p']").checked;
    var sibling_divs = document.querySelectorAll('.siblings');
    for (var i = 0; i < sibling_divs.length; i++) {
        if (checked) {
            sibling_divs[i].classList.add('sib-pic');
        } else {
            sibling_divs[i].classList.remove('sib-pic');
        }
    }
}

function load_thumbs() {
    var delayed = document.querySelectorAll("img[src-delay]");
    for (var i = 0; i < delayed.length; i++) {
        var img = delayed[i];
        var src = img.getAttribute('src-delay');
        img.setAttribute('src', src);
    }
}

function show_sort() {
    var first = document.querySelector('input[name="show"]').parentElement;
    var checked = document.querySelectorAll('input[name="show"]:checked');
    if (checked[0]) {
        first.before(checked[0].parentElement);
        for (var c = 1; c < checked.length; c++) {
            checked[c - 1].parentElement.after(checked[c].parentElement);
        }
    }
}

function depth_get() {
    return parseInt(document.querySelector("input[name='depth']:checked").value);
}

function show_get() {
    var show = '';
    var checked = document.querySelectorAll("input[name='show']:checked");
    for (var c = 0; c < checked.length; c++) {
        show += checked[c].value;
    }
    return show;
}

function show_set(show, replace = false) {
    if (replace) {
        var checked = document.querySelectorAll("input[name='show']:checked");
        checked.forEach(function(node) {
            node.checked = false;
        });
    }
    for (let s of show) {
        document.getElementById(s).checked = true;
    }
    document.getElementById('show_fieldset').dispatchEvent(new Event('change'));
}


function show_branch(event) {
    event.cancelBubble = true;
    var spouse_div = event.currentTarget;
    var spouses_div = spouse_div.parentElement;
    spouses_div.classList.toggle('hide');
    var parent_div = spouses_div.parentElement;
    var brail_button = parent_div.querySelector(".brail");
    brail_button.classList.remove("hide");
    var generation_div = spouse_div.closest(".generation");
    var gen = generation_div.getAttribute('gen');
    var branch_index = parent_div.getAttribute('branch');
    var next_gen_div = generation_div.querySelector('.next_gen');
    var chute_div = generation_div.querySelector('div.chutes');
    var branches = next_gen_div.children;
    var chutes = chute_div.children;
    for (let i = 0; i < branches.length; i++) {
        chute = chutes[i];
        branch = branches[i];
        var index = branch.getAttribute('branch');
        if (index == branch_index) {
            chute.classList.remove('hide');
            branch.classList.remove('hide');
        }
    }
    packDescendants(show_get());
    resize_chutes(event);
}


function hide_branch(event) {
    event.cancelBubble = true;
    var button = event.target;
    button.classList.add('hide');
    var parent_div = button.parentElement;
    var spouses_div = parent_div.querySelector(".spouses");
    spouses_div.classList.remove('hide');
    var generation_div = parent_div.closest(".generation");
    var branch_index = parent_div.getAttribute('branch');
    var next_gen_div = generation_div.querySelector('.next_gen');
    var chute_div = generation_div.querySelector('div.chutes');
    var branches = next_gen_div.children;
    var chutes = chute_div.children;
    for (let i = 0; i < branches.length; i++) {
        chute = chutes[i];
        branch = branches[i];
        var index = branch.getAttribute('branch');
        if (index == branch_index) {
            chute.classList.add('hide');
            branch.classList.add('hide');
        }
    }
    packDescendants(show_get());
    resize_chutes(event);
}


function show_all_descendants(event) {
    event.cancelBubble = true;
    document.getElementById('settings').dispatchEvent(new MouseEvent('click'));
    var buttons = document.querySelectorAll('button.radio');
    for (let i = 0; i < buttons.length; i++) {
        buttons[i].dispatchEvent(new MouseEvent('click'));
    }
}


function help(event) {
    event.cancelBubble = true;
    var help_button = event.target;
    help_button.classList.toggle('checked');
    var help = document.querySelectorAll('.help');
    if (help_button.classList.contains('checked')) {
        document.getElementById('profile').classList.add('hide');
        help.forEach(function(node) {
            node.classList.remove('hide')
        });
        help_button.setAttribute('hold', show_get());
        show_set('rl', true);
    } else {
        document.getElementById('profile').classList.remove('hide');
        help.forEach(function(node) {
            node.classList.add('hide')
        });
        show_set(help_button.getAttribute('hold'), true);
    }
}

function resize(event) {
    pack();
    resize_chutes(event);
}

function resize_chutes(event) {
    var target = event.currentTarget;
    var gen_chutes = document.querySelectorAll('div.chutes');
    for (let i = 0; i < gen_chutes.length; i++) {
        var chutes_div = gen_chutes[i];
        var generation_div = chutes_div.closest(".generation");
        var g = parseInt(generation_div.getAttribute('gen')) + 1;
        if (!chutes_div) {
            continue;
        }
        var height = chutes_div.getBoundingClientRect().height;
        var chutes = chutes_div.children;
        for (let j = 0; j < chutes.length; j++) {
            var chute = chutes[j];
            var polygon = chute.querySelector('polygon');
            var b = chute.getAttribute('branch');
            var head_query = "div.siblings > .person[branch='" + b + "']";
            var head = generation_div.querySelector(head_query);
            var branch_query = "div.next_gen[gen='" + g + "'] > [branch='" + b + "']";
            var branch = generation_div.querySelector(branch_query);

            if (!polygon || !head || !branch) {
                continue;
            }

            var offset = generation_div.getBoundingClientRect().left;
            var head = head.getBoundingClientRect();
            var top_left = (head.left - offset) + ",0";
            var top_right = (head.right - offset) + ",0";

            var offset = branch.parentElement.getBoundingClientRect().left;
            var branch = branch.getBoundingClientRect();
            var bottom_left = (branch.left - offset) + "," + height;
            var bottom_right = (branch.right - offset) + "," + height;

            var points = bottom_left + " " + top_left + " " + top_right + " " + bottom_right;
            polygon.setAttribute("points", points);
        }
    }
}


function pack() {
    var ancestors = document.getElementById("ancestors");
    unpack(ancestors);
    var depth = depth_get();
    var g4_packed = packAncestors(show_get(), depth);
    var depth = hideSurplus();
    if (depth <= 4 && g4_packed < 1) {
        // reduce font-size if gen4 not fully packed
        document.getElementById("ancestors").style.fontSize = "1.0em";
    }
    unpack(document.getElementById("descendants"));
    packDescendants(show_get());
}


function hideSurplus() {
    // hide generations with no visible data
    var data_depth = document.getElementById('get').getAttribute('depth');
    var depth = depth_get();
    var ancestors = document.getElementById("ancestors");
    var deepest = data_depth;
    for (var g = data_depth; g > 4; g--) {
        var visible = "[gen='" + g + "'] span[p]:not(.X)";
        var hide = g > depth ||
            ancestors.querySelectorAll(visible).length < 4;
        var profiles = ancestors.querySelectorAll("[gen='" + g + "']");
        for (var p = 0; p < profiles.length; p++) {
            var div = profiles[p].parentElement;
            if (hide) {
                div.classList.add("frac_hide");
                div.parentElement.classList.add("fractal_x");
            } else {
                div.classList.remove("frac_hide");
                div.parentElement.classList.remove("fractal_x");
            }
        }
        deepest = hide ? g - 1 : deepest;
    }
    return deepest;
}


function unpack(element) {
    var all = element.querySelectorAll("span[p]");
    for (let a = 0; a < all.length; a++) {
        all[a].classList.add('X');
    }
}


function packAncestors(show, depth_max) {
    if (show.length = 0) {
        return 1; // unwind recursion
    }
    var g4_packed = 0;

    // try to show the data in priority order
    var ancestors = document.getElementById("ancestors");
    var data = "[p='" + show[0] + "']";
    var g = 0;
    do { // work out from the center
        var gen_g = "[gen='" + g + "']";
        var gen_g_data = gen_g + " " + data;
        var packed = pack_square(ancestors.querySelectorAll(gen_g_data));
        g4_packed = g == 4 ? packed : g4_packed;
        var gen_len = ancestors.querySelectorAll(gen_g).length;
        g++;
    } while (g <= depth_max && gen_len > 0 && packed > 0.2);

    // pack the next priority after a short delay
    setTimeout(packAncestors, 10, show.substring(1), depth_max);

    return g4_packed;
}


function pack_square(nodes) {
    if (nodes.length == 0) {
        return 1;
    }

    var ancestors = document.getElementById("ancestors");
    // hide root siblings if already too tall
    if (overflow(ancestors)) {
        var siblings = document.getElementById('root_siblings');
        siblings.classList.add('hide');
    }

    // show the data for all of the people
    var shown = [];
    for (let n = 0; n < nodes.length; n++) {
        nodes[n].classList.remove('X');
        shown.push(nodes[n]);
    }

    //check if div #ancestors has exceeded the bounds of screen
    if (overflow(ancestors)) {
        shown.sort((a, b) => {
            b.innerText.length - a.innerText.length
        });
        while (overflow(ancestors) && shown.length > 0) {
            var hide = shown.splice(Math.trunc(0.9 * shown.length));
            hide.forEach((v, i, a) => {
                v.classList.add('X')
            });
        }
    }
    return shown.length / nodes.length;
}


function overflow(element) {
    var body = document.body;
    return body.scrollWidth > body.clientWidth ||
        element.offsetHeight > window.innerHeight ||
        element.offsetHeight > body.clientWidth;
}


function packDescendants(show) {
    var descendants = document.getElementById("descendants");
    if (!descendants) {
        return;
    }
    for (let s = 0; s < show.length; s++) {
        var data = "[p='" + show[s] + "']";
        var g = 1;
        do { // pack each generation separately
            var gen_g = "[gen='" + g + "']";
            var gen_g_data = gen_g + " " + data;
            var nodes = descendants.querySelectorAll(gen_g_data);
            pack_width(nodes);
            g++;
        } while (nodes.length > 0);
    }
}

function pack_width(nodes) {
    // show the data for all of the people
    for (let n = 0; n < nodes.length; n++) {
        nodes[n].classList.remove('X');
    }
    shown = Array.prototype.slice.call(nodes, 0);
    shown.sort((a, b) => {
        b.innerText.length - a.innerText.length
    });

    // check for screen width exceeded, and progressively hide to fix
    var body = document.body;
    while (body.scrollWidth > body.clientWidth && shown.length > 0) {
        var hide = shown.splice(Math.trunc(0.9 * shown.length));
        hide.forEach((v, i, a) => {
            v.classList.add('X')
        });
    }
}


const search_settings = [{
    action: 'searchPerson',
    dateInclude: 'both',
    dateSpread: 0,
    skipVariants: 1,
    lastNameMatch: 'strict',
    limit: 100,
    fields: 'Id,Name,FirstName,MiddleName,LastNameCurrent,LastNameAtBirth,Gender,BirthDate,DeathDate,BirthLocation,DeathLocation,Father,Mother',
}, {
    dateSpread: 1,
}, {
    dateSpread: 2,
    lastNameMatch: 'all',
}, {
    dateSpread: 5,
    skipVariants: 0,
}, {
    dateSpread: 10,
    dateInclude: 'neither',
}, ];


function search_click(event) {
    event.cancelBubble = true;
    var results_msg = document.getElementById("results_msg");
    var results_table = document.getElementById("results_table");
    var results_count = document.getElementById("results_count");
    document.getElementById('results_div').classList.remove('hide');
    var spin = results_msg.classList.add('spin');
    results_table.innerHTML = '';
    results_count.innerHTML = '0';
    // autofill child_last from father_last
    var child_last = document.getElementById('child_last');
    if (!child_last.value) {
        child_last.value = document.getElementById('father_last').value;
    }

    var terms = {
        FirstName: document.getElementById('child_first').value,
        LastName: document.getElementById('child_last').value,
        BirthDate: parseInt(document.getElementById('child_birth_date').value),
        DeathDate: parseInt(document.getElementById('child_death_date').value),
        BirthLocation: document.getElementById('child_birth_location').value,
        DeathLocation: document.getElementById('child_death_location').value,
        fatherFirstName: document.getElementById('father_first').value,
        fatherLastName: document.getElementById('father_last').value,
        motherFirstName: document.getElementById('mother_first').value,
        motherLastName: document.getElementById('mother_last').value,
    }

    search(terms, structuredClone(search_settings[0]));
}

function more_results(event) {
    event.cancelBubble = true;
    event.preventDefault();
    var results = document.getElementById('results_table');
    var rows = results.querySelectorAll('tr.hide');
    if (rows.length < 16) {
        event.target.classList.add('hide');
    }
    for (var i = 0; i < Math.min(rows.length, 16); i++) {
        rows[i].classList.remove('hide');
    }

}

function search(terms, settings, attempt = 0) {
    var param = new URLSearchParams(Object.assign(terms, settings));
    var url = "search.php?" + param.toString();

    var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var response = JSON.parse(this.responseText)[0];
            var status = response['status'];
            var total = response['total'];
            if (status == 0) {
                results_count.innerHTML = total;
                results_msg.innerHTML = '';
                show_matches(terms, response['matches'], results_table);
                attempt++;
                if (total < 100 && attempt < search_settings.length) {
                    Object.assign(settings, search_settings[attempt]);
                    search(terms, settings, attempt);
                } else {
                    results_msg.classList.remove('spin');
                }
            } else {
                results_msg.innerHTML = status;
            }
        }
    };
    xmlhttp.open("GET", url, true);
    xmlhttp.send();

    function show_matches(terms, matches, table, ) {
        gap(terms, matches);
        matches.sort(function(a, b) {
            return a['gap'] - b['gap']
        });
        table.innerHTML = match_rows(matches);
    }

    function match_rows(matches) {
        var rows = "<tr><th>WikiTree ID</th><th>Name</th><th>Birth</th><th>Death</th></tr>";
        var more = '';
        var show = show_get().replace('r', ''); // special case: prevent 'r'  propogating from help
        var depth = depth_get();
        for (i = 0; i < matches.length; i++) {
            var m = matches[i];
            if (!m['Name']) {
                continue;
            }
            var key = m['Name'];
            var first = m['FirstName'] ? m['FirstName'] : '';
            var middle = m['MiddleName'] ? m['MiddleName'] : '';
            var born = m['LastNameAtBirth'] ? m['LastNameAtBirth'] : '';
            var died = m['LastNameCurrent'] ? m['LastNameCurrent'] : '';
            var birth_date = m['BirthDate'] ? m['BirthDate'] : '';
            var death_date = m['DeathDate'] ? m['DeathDate'] : '';
            var birth_location = m['BirthLocation'] ? m['BirthLocation'] : '';
            var death_location = m['DeathLocation'] ? m['DeathLocation'] : '';
            var last = (born === died) ? born : '(' + born + ') ' + died;
            var id_span = "<span class='nowrap'>" + key + "</span>";
            var param = "key=" + key + "&show=" + show + "&depth=" + depth;
            var row = "<td><a href='index.php?" + param + "'>" + id_span + '</a></td>';
            row += '<td>' + first + ' ' + middle + ' ' + last + '</td>';
            row += '<td>' + birth_date + '<br>' + birth_location + '</td>';
            row += '<td>' + death_date + '<br>' + death_location + '</td>';
            //row += '<td>' + m['gap'] + '</td>';
            if (i < 16) {
                row = '<tr>' + row + '</tr>';
            } else {
                row = "<tr class='hide'>" + row + '</tr>';
                more = "<tr><td></td><td><a href='' onclick='more_results(event)'>more...</a></td></tr>";
            }
            rows += row;
        }
        rows += more;
        return rows;
    }


    function gap(terms, matches) {
        for (var i = 0; i < matches.length; i++) {
            gap_total = 0;
            if (terms.BirthDate) {
                var date = matches[i]['BirthDate'] ? matches[i]['BirthDate'] : '0000';
                var year = date.substr(0, 4);
                var gap = Math.abs(terms.BirthDate - +year);
                var gh = gap ? 'gap' : 'hit';
                matches[i]['BirthDate'] = "<span class='" + gh + "'>" + year + "</span>" + date.substr(4);
                gap_total += +year == 0 ? 4 : gap;
            }
            if (terms.DeathDate) {
                var date = matches[i]['DeathDate'] ? matches[i]['DeathDate'] : '0000';
                var year = date.substr(0, 4);
                var gap = Math.abs(terms.DeathDate - +year);
                var gh = gap ? 'gap' : 'hit';
                matches[i]['DeathDate'] = "<span class='" + gh + "'>" + year + "</span>" + date.substr(4);
                gap_total += +year == 0 ? 4 : gap;
            }
            if (terms.FirstName) {
                first = matches[i]['FirstName'] ? matches[i]['FirstName'] : '';
                middle = matches[i]['MiddleName'] ? matches[i]['MiddleName'] : '';
                first_gap = levenshtein(terms.FirstName, first);
                middle_gap = levenshtein(terms.FirstName, middle);
                if (first_gap && middle_gap) {
                    matches[i]['FirstName'] = "<span class='gap'>" + first + "</span>";
                    matches[i]['MiddleName'] = "<span class='gap'>" + middle + "</span>";
                } else if (first_gap) {
                    matches[i]['MiddleName'] = "<span class='hit'>" + middle + "</span>";
                } else if (middle_gap) {
                    matches[i]['FirstName'] = "<span class='hit'>" + first + "</span>";
                } else {
                    matches[i]['FirstName'] = "<span class='hit'>" + first + "</span>";
                    matches[i]['MiddleName'] = "<span class='hit'>" + middle + "</span>";
                }
                gap_total += Math.min(first_gap, middle_gap);
            }
            if (terms.LastName) {
                born = matches[i]['LastNameAtBirth'] ? matches[i]['LastNameAtBirth'] : '';
                died = matches[i]['LastNameCurrent'] ? matches[i]['LastNameCurrent'] : '';
                born_gap = levenshtein(terms.LastName, born);
                died_gap = levenshtein(terms.LastName, died);
                if (born_gap && died_gap) {
                    matches[i]['LastNameAtBirth'] = "<span class='gap'>" + born + "</span>";
                    matches[i]['LastNameCurrent'] = "<span class='gap'>" + died + "</span>";
                } else if (born_gap) {
                    matches[i]['LastNameCurrent'] = "<span class='hit'>" + died + "</span>";
                } else if (died_gap) {
                    matches[i]['LastNameAtBirth'] = "<span class='hit'>" + born + "</span>";
                } else {
                    matches[i]['LastNameAtBirth'] = "<span class='hit'>" + born + "</span>";
                    matches[i]['LastNameCurrent'] = "<span class='hit'>" + died + "</span>";
                }
                gap_total += Math.min(born_gap, died_gap);
            }
            matches[i]['gap'] = gap_total;
        }
    }
}

function full_monty() {
    document.getElementById('settings').dispatchEvent(new MouseEvent('click'));
    var all = document.querySelectorAll("span[p]");
    for (let a = 0; a < all.length; a++) {
        all[a].classList.remove('X');
    }
}