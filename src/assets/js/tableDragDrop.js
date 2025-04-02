
dragDropInit = (componentId, updateMethod) => {
    let genericTableDragula = dragula([document.querySelector('#' +componentId+' tbody')], {
        moves: function (el, container, handle) {
            return handle.classList.contains('generic_handle');
        }
    });
    
    
    genericTableDragula.on('cloned', (clone, origin, type) => {
        clone.classList.add('d-flex', 'justify-content-between', 'bg-black', 'text-white');
    })
    
    genericTableDragula.on('drop', (el, target, source, sibling) => {
        updateMethod(el, sibling);
    })
}