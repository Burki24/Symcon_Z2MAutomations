// Fail before launching a browser if the acceptance script enables image capture.
export function assertNoBrowserCapture(source) {
    const forbidden = /\b(?:screenshot|toHaveScreenshot|recordVideo|tracing|startTracing|captureScreenshot|startScreencast|getDisplayMedia|desktopCapturer)\b/i;
    const feature = source.match(forbidden)?.[0];
    if (feature) throw new Error(`Browser capture is forbidden in this offline test: ${feature}`);
}
