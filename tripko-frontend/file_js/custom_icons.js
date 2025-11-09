// Shared custom icon logic for TripKo maps
function getCustomIcon(category) {
  let bgColor, iconHtml;
  // Normalize category for comparison (remove spaces, dashes, underscores, emoji, lowercase)
  const catRaw = (category || '').toLowerCase().replace(/&#128652;|🚌/g, '').trim();
  const cat = catRaw.replace(/\s|_|-/g, '');
  // Bus station variants (add 'terminal' and more for robustness)
  const isBusStation = [
    'busstation', 'bus', 'busterminal', 'busterminalstation', 'busstop', 'bus_stand', 'busstand', 'bus_terminal', 'bus_station', 'terminal', 'terminalbus', 'pangasinanbusstation', 'busstations', 'busstationspangasinan', 'busstationpangasinan'
  ].includes(cat);
  switch (true) {
    case cat === 'beach':
      bgColor = '#00bcd4';
      iconHtml = '<i class="fas fa-umbrella-beach"></i>';
      break;
    case cat === 'caves':
    case cat === 'cave':
      bgColor = '#795548';
      iconHtml = '<i class="fas fa-mountain"></i>';
      break;
    case cat === 'islands':
    case cat === 'island':
      bgColor = '#4caf50';
      iconHtml = '<i class="fas fa-water"></i>';
      break;
    case cat === 'churches':
    case cat === 'church':
      bgColor = '#B03A2E';
      iconHtml = '<i class="fas fa-church"></i>';
      break;
    case cat === 'festival':
      bgColor = '#ff9800';
      iconHtml = '<i class="fas fa-star"></i>';
      break;
    case 'waterfalls':
    case cat === 'waterfall':
    case cat === 'falls':
      bgColor = '#2196f3';
      iconHtml = '<i class="fas fa-water"></i>';
      break;
    case isBusStation:
      // Use a PNG bus icon for best compatibility
      bgColor = 'transparent';
      iconHtml = `<img src="https://cdn.jsdelivr.net/gh/charlesstover/leaflet-marker-icon@master/images/marker-icon-bus.png" style="width:28px;height:28px;display:block;margin:auto;" alt="Bus Terminal"/>`;
      break;
    default:
      bgColor = '#607d8b'; // Use a neutral but styled color
      iconHtml = '<i class="fas fa-location-dot"></i>'; // Use a styled location-dot icon instead of the default marker
  }

  return L.divIcon({
    html: `
      <div style="position: relative; width: 30px; height: 42px;">
        <div style="
          width: 30px; height: 30px; background: ${bgColor}; border-radius: 50%;
          display: flex; align-items: center; justify-content: center;
          color: white; font-size: 18px; z-index: 2;">
          ${iconHtml}
        </div>
        <div style="
          width: 0; height: 0; border-left: 10px solid transparent;
          border-right: 10px solid transparent; border-top: 12px solid ${bgColor};
          position: absolute; top: 28px; left: 5px; z-index: 1;"></div>
      </div>`,
    className: '',
    iconSize: [30, 42],
    iconAnchor: [15, 42],
    popupAnchor: [0, -35]
  });
}