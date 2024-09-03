function resetFilters() {
    // Obtiene la URL actual sin los parámetros de consulta
    const url = new URL(window.location.href);
    
    // Elimina los parámetros de filtro de fecha y de búsqueda
    url.searchParams.delete('start_date');
    url.searchParams.delete('end_date');
    url.searchParams.delete('search'); // Elimina el parámetro de búsqueda
    
    // Redirige a la URL limpia
    window.location.href = url.toString();
}
