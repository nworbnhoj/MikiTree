function load(event) {
    var div = event.currentTarget;
    var key = div.id;
    if (key) {
        div.classList.add('spin');
        div.firstElementChild.classList.add('hide');
        window.location.href = "index.php?key=" + key;
        setTimeout(unspin, 5000, div);
    } else {
        alert("Sorry the Wiki-ID is missing for this Profile for some reason.");
    }
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