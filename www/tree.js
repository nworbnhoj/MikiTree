function load(event) {
    event.cancelBubble = true;
    var div = event.currentTarget;
    var key = div.id;
    if (!key) {
        alert("Sorry the WikiTree-ID is missing for this Profile for some reason.");
        return;
    }
    div.classList.add('spin');
    var params = {
        'key': key,
        'depth': depth_get(),
        'show': show_get(),
    };
    var esc = encodeURIComponent;
    var query = Object.keys(params)
        .map(k => esc(k) + '=' + esc(params[k]))
        .join('&');
    window.location.href = "index.php?" + query;
    setTimeout(unspin, 5000, div);
}


function unspin(element) {
    element.classList.remove('spin');
    element.firstElementChild.classList.remove('hide');
}

function settings(event){
    event.cancelBubble = true;
    event.currentTarget.classList.toggle('checked');
    document.getElementById('settings').classList.toggle('hide');
}

function depth_changed(event) {
    event.cancelBubble = true;
    var get = document.getElementById('get');
    var data_depth = get.getAttribute('depth');
    var depth = depth_get();
    if (depth > data_depth) {
        get.setAttribute('depth', depth);
        load(event);
    } else {
        resize(event);
    }
}

function show_changed(event){
    event.cancelBubble = true;
    var middle = document.querySelector("input[name='show'][value='m']");
    var m_initial = document.querySelector("input[name='show'][value='M']");
    if (middle.checked && m_initial.checked){
        m_initial.checked = false;
    }
    var last = document.querySelector("input[name='show'][value='l']");
    var l_bold = document.querySelector("input[name='show'][value='L']");
    if (last.checked && l_bold.checked){
        l_bold.checked = false;
    }
    show_sort();
    pack();
}

function show_sort(){
    var first = document.querySelector('input[name="show"]').parentElement;
    var checked = document.querySelectorAll('input[name="show"]:checked');
    if(checked[0]){
        first.before(checked[0].parentElement);
        for(var c=1; c<checked.length; c++){
            checked[c-1].parentElement.after(checked[c].parentElement);
        }
    }
}

function depth_get() {    
    return parseInt(document.querySelector("input[name='depth']:checked").value);
}

function show_get() {
    var show = '';
    var checked = document.querySelectorAll('input[name="show"]:checked');
    for(var c=0; c<checked.length; c++){
        show += checked[c].value;
    }
    return show;
}

function showBranch(event) {
    event.cancelBubble = true;
    var radio_button = event.currentTarget;
    var gen = radio_button.getAttribute('gen');
    var checked = radio_button.classList.toggle('checked');
    var parent_div = radio_button.closest(".person");
    var spouses_div = parent_div.querySelector('div.spouses');
    spouses_div.classList.toggle('hide');
    var generation_div = radio_button.closest(".generation");
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
            if (checked) {
                chute.classList.remove('hide');
                branch.classList.remove('hide');
            } else {
                chute.classList.add('hide');
                branch.classList.add('hide');
            }
        }
    }
    resize_chutes(event);
}

function show_all_descendants(event) {
    event.cancelBubble = true;
    document.getElementById('settings_toggle').dispatchEvent(new MouseEvent('click'));
    var buttons = document.querySelectorAll('button.radio');
    for (let i = 0; i < buttons.length; i++) {
        buttons[i].dispatchEvent(new MouseEvent('click'));
    }
}


function help(event) {
    var div = event.currentTarget;
    if (div.classList.contains('checked')) {
        window.location.href = 'index.php#bio';
    } else {
        window.location.href = 'index.php';
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
    var get = document.getElementById('get');
    var depth = depth_get();
    var ancestors = document.getElementById("ancestors");
    while (depth > 4) {
        var visible = "[gen='" + depth + "'] span[p]:not(.X)";
        var hide = ancestors.querySelectorAll(visible).length < 4;
        var generation = ancestors.querySelectorAll("[gen='" + depth + "']");
        for (var g = 0; g < generation.length; g++) {
            if (hide) {
                generation[g].classList.add("hide");
            } else {
                generation[g].classList.remove("hide");
            }
        }
        depth--;
    }
    return depth;
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
        var packed = packNodes(ancestors.querySelectorAll(gen_g_data));
        g4_packed = g == 4 ? packed : g4_packed;
        var gen_len = ancestors.querySelectorAll(gen_g).length;
        g++;
    } while (g <= depth_max && gen_len > 0 && packed > 0.2);

    // pack the next priority after a short delay
    setTimeout(packAncestors, 10, show.substring(1), depth_max);

    return g4_packed;
}


function packNodes(nodes) {
    if (nodes.length == 0) {
        return 1;
    }
    var all = nodes.length;

    var body = document.body;
    var ancestors = document.getElementById("ancestors");
    // hide root siblings if already too tall
    if (ancestors.offsetHeight > window.innerHeight ||
        ancestors.offsetHeight > body.clientWidth) {
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
    if (body.scrollWidth > body.clientWidth ||
        ancestors.offsetHeight > window.innerHeight ||
        ancestors.offsetHeight > body.clientWidth) {
        shown.sort((a, b) => {
            b.innerText.length - a.innerText.length
        });
        while (shown.length > 0) {
            var hide = shown.splice(Math.trunc(0.9 * shown.length));
            hide.forEach((v, i, a) => {
                v.classList.add('X')
            });
            if (body.scrollWidth <= body.clientWidth &&
                ancestors.offsetHeight <= window.innerHeight &&
                ancestors.offsetHeight <= body.clientWidth) {
                break;
            }
        }
    }
    return shown.length / nodes.length;
}


function packDescendants(show) {
    var descendants = document.getElementById("descendants");
    if (!descendants) {
        return;
    }
    for (let s = 0; s < show.length; s++) {
        var data = "[p='" + show[s] + "']";
        var nodes = descendants.querySelectorAll(data);
        for (let n = 0; n < nodes.length; n++) {
            nodes[n].classList.remove('X');
        }
    }
}