
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
        
        let childs = Array.from(target.children);
        console.log(childs)
        childs.forEach( (e, i) => {
            if(el == e) {
                updateMethod(i, e);
                return;
            }
        })
    })
}