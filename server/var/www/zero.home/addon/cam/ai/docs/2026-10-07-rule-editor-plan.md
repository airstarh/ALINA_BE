# Human Detection rule editor plan

1. Expose the first native PedRule line and region in normalized 0–8192 coordinates. Accept optional geometry changes only after a fresh-read comparison, validate them, merge only the selected point arrays, and verify read-back.
2. Serve a still JPEG through PHP for the selected configured camera. Do not put camera credentials or direct camera URLs in browser state.
3. Add one collapsed Rule area beneath Human Detection settings. Overlay SVG on the actual image bounds; offer endpoint dragging for Tripwire and corner/edge dragging for the four-sided Intrusion area. Preserve the inactive geometry and provide local discard.
4. Include geometry in the existing Review changes / Save flow. Test model, validation, coordinate mapping at multiple image sizes, and no-change behavior. Verify with unit tests and read-only live requests; do not alter live rules during verification.

No JavaScript build or Git commit is needed.
