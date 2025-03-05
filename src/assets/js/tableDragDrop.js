
let genericTableDragula = dragula([document.querySelector('#generic_table tbody')], {
        
    moves: function (el, container, handle) {
        return handle.classList.contains('generic_handle');
    }
});


genericTableDragula.on('cloned', (clone, origin, type) => {
    clone.classList.add('d-flex', 'justify-content-between', 'bg-black', 'text-white');
})

genericTableDragula.on('drop', (el, target, source, sibling) => {
    let childs = Array.from(target.children);
    childs.forEach( (e, i) => {
        if(el == e) {
            Livewire.dispatch('orderingCompleted', {order: i, data: e.getAttribute('ordering-data')});
            return;
        }
    })
})