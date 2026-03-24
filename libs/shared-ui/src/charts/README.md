# Guard51 SVG Charts

Custom Angular standalone components rendering `<svg>` elements directly.
Zero runtime dependencies. Built in Phase 0H.

## Components

- `svg-line-chart` — Time series (attendance trends, incident frequency)
- `svg-bar-chart` — Comparisons (guards per site, incidents by type)
- `svg-stacked-bar-chart` — Composition over time (hours by shift type)
- `svg-pie-chart` — Distribution (incident types, payment methods)
- `svg-donut-chart` — Distribution with center metric (shift compliance %)
- `svg-gauge-chart` — Single KPI (attendance rate, patrol completion)
- `svg-sparkline` — Inline mini trend (stats card trend indicators)
- `svg-heatmap` — Grid intensity (guard coverage by day/hour)
- `svg-area-chart` — Filled time series (revenue cumulative)
- `chart-utils` — Shared: scales, axes, tooltips, responsive, animations

## Design Principles

- **Responsive:** SVG viewBox scales to container width, components use ResizeObserver
- **Accessible:** ARIA labels, keyboard navigation, screen reader descriptions
- **Animated:** CSS transitions on path/rect/circle elements (GPU-accelerated)
- **Themed:** Colors pulled from BrandingService (white-label tenant colors)
- **Lightweight:** Each component ~200-400 lines, total library <3KB gzipped
