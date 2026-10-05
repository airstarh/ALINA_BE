# Ten general, camera, and detection editors

This batch adds dedicated forms for:

- General preferences → `General.General`
- Locale and display formats → `General.Location`
- Defog → `Camera.ClearFog`
- White-light control → `Camera.WhiteLight`
- Overlay indicators → `fVideo.GUISet`
- OSD logo → `fVideo.OsdLogo`
- Output volume → `fVideo.Volume`
- Input volume → `fVideo.VolumeIn`
- Detection tracking → `Detect.DetectTrack`
- Human detection → `Detect.HumanDetection`

The human-detection editor deliberately excludes detection regions, line and
area rules, schedules, and event actions. Those structures remain untouched in
the fresh complete branch used for every save. Locale DST start/end structures,
GUI fields not shown in the form, and camera image-processing metadata are also
preserved.

Live read-only requests confirmed that Balcon accepts each dotted branch getter
and that all field shapes match the schemas. No camera value was written during
verification.
