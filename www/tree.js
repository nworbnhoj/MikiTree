function load(event) {
    event.cancelBubble = true;
    var div = event.currentTarget;
    var key = div.id;
    if (!key) {
        alert("Sorry the Wiki-ID is missing for this Profile for some reason.");
        return;
    }
    div.classList.add('spin');
    div.firstElementChild.classList.add('hide');
    var get = document.getElementById('get');
    var params = {
        'key': key,
        'depth': get.getAttribute('depth'),
        'show': get.getAttribute('show'),
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
    unpack();
    var g4_packed = packAncestors();
    var depth = hideSurplus();
    if (depth <= 4 && g4_packed < 1) {
        // reduce font-size if gen4 not fully packed
        document.getElementById("ancestors").style.fontSize = "1.0em";
    }
    packDescendants();
}


function hideSurplus() {
    // hide generations with no visible data
    var get = document.getElementById('get');
    var depth = parseInt(get.getAttribute('depth'));
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


function unpack() {
    var all = document.querySelectorAll("span[p]");
    for (let a = 0; a < all.length; a++) {
        all[a].classList.add('X');
    }
}


function packAncestors(priority = "lfym") {
    if (priority.length = 0) {
        return 1; // unwind recursion
    }
    var g4_packed = 0;

    // try to show the data in priority order
    var ancestors = document.getElementById("ancestors");
    var data = "[p='" + priority[0] + "']";
    var g = 0;
    do { // work out from the center
        var gen_g = "[gen='" + g + "']";
        var gen_g_data = gen_g + " " + data;
        var nodes = ancestors.querySelectorAll(gen_g_data);
        var packed = packNodes(nodes);
        g4_packed = (g <= 4) ? packed : g4_packed;
        g++;
    } while (nodes.length > 0 && packed > 0.2);

    // pack the next priority after a short delay
    setTimeout(packAncestors, 10, priority.substring(1));

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

    // check if div #ancestors has exceeded the bounds of screen

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


function packDescendants(priority = "lfym") {
    var descendants = document.getElementById("descendants");
    for (let p = 0; p < priority.length; p++) {
        var data = "[p='" + priority[p] + "']";
        var nodes = descendants.querySelectorAll(data);
        for (let n = 0; n < nodes.length; n++) {
            nodes[n].classList.remove('X');
        }
    }
}