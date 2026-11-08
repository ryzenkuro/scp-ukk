/**
 * Pico.js - High performance real-time face detection library
 * Ported to strict TypeScript for Vite & ESM bundler compatibility.
 * Original algorithm by Nenad Markuš (MIT License).
 */

export interface PicoImage {
  pixels: Uint8Array;
  nrows: number;
  ncols: number;
  ldim: number;
}

export interface PicoCascadeParams {
  shiftfactor: number;
  minsize: number;
  maxsize: number;
  scalefactor: number;
}

export type ClassifyRegionFunction = (
  r: number,
  c: number,
  s: number,
  pixels: Uint8Array,
  ldim: number
) => number;

export function unpack_cascade(bytes: Int8Array): ClassifyRegionFunction {
  const dview = new DataView(new ArrayBuffer(4));
  let p = 8;

  dview.setUint8(0, bytes[p + 0]);
  dview.setUint8(1, bytes[p + 1]);
  dview.setUint8(2, bytes[p + 2]);
  dview.setUint8(3, bytes[p + 3]);
  const tdepth = dview.getInt32(0, true);
  p += 4;

  dview.setUint8(0, bytes[p + 0]);
  dview.setUint8(1, bytes[p + 1]);
  dview.setUint8(2, bytes[p + 2]);
  dview.setUint8(3, bytes[p + 3]);
  const ntrees = dview.getInt32(0, true);
  p += 4;

  const tcodesArr: number[] = [];
  const tpredsArr: number[] = [];
  const threshArr: number[] = [];

  for (let t = 0; t < ntrees; ++t) {
    Array.prototype.push.apply(tcodesArr, [0, 0, 0, 0]);
    Array.prototype.push.apply(tcodesArr, Array.from(bytes.slice(p, p + 4 * Math.pow(2, tdepth) - 4)));
    p += 4 * Math.pow(2, tdepth) - 4;

    for (let i = 0; i < Math.pow(2, tdepth); ++i) {
      dview.setUint8(0, bytes[p + 0]);
      dview.setUint8(1, bytes[p + 1]);
      dview.setUint8(2, bytes[p + 2]);
      dview.setUint8(3, bytes[p + 3]);
      tpredsArr.push(dview.getFloat32(0, true));
      p += 4;
    }

    dview.setUint8(0, bytes[p + 0]);
    dview.setUint8(1, bytes[p + 1]);
    dview.setUint8(2, bytes[p + 2]);
    dview.setUint8(3, bytes[p + 3]);
    threshArr.push(dview.getFloat32(0, true));
    p += 4;
  }

  const tcodes = new Int8Array(tcodesArr);
  const tpreds = new Float32Array(tpredsArr);
  const thresh = new Float32Array(threshArr);

  return function (
    r: number,
    c: number,
    s: number,
    pixels: Uint8Array,
    ldim: number
  ): number {
    r = 256 * r;
    c = 256 * c;
    let root = 0;
    let o = 0.0;
    const pow2tdepth = Math.pow(2, tdepth) >> 0;

    for (let i = 0; i < ntrees; ++i) {
      let idx = 1;
      for (let j = 0; j < tdepth; ++j) {
        const k = root + 4 * idx;
        const q1 = ((r + tcodes[k + 0] * s) >> 8) * ldim + ((c + tcodes[k + 1] * s) >> 8);
        const q2 = ((r + tcodes[k + 2] * s) >> 8) * ldim + ((c + tcodes[k + 3] * s) >> 8);
        idx = 2 * idx + (pixels[q1] > pixels[q2] ? 1 : 0);
      }
      o += tpreds[pow2tdepth * i + idx - pow2tdepth];
      if (o <= thresh[i]) return -1;
      root += 4 * pow2tdepth;
    }

    return o - thresh[ntrees - 1];
  };
}

export function run_cascade(
  image: PicoImage,
  classifyRegion: ClassifyRegionFunction,
  params: PicoCascadeParams
): number[][] {
  const pixels = image.pixels;
  const nrows = image.nrows;
  const ncols = image.ncols;
  const ldim = image.ldim;

  const shiftfactor = params.shiftfactor;
  const minsize = params.minsize;
  const maxsize = params.maxsize;
  const scalefactor = params.scalefactor;

  let scale = minsize;
  const detections: number[][] = [];

  while (scale <= maxsize) {
    const step = Math.max(2, Math.floor(scale * shiftfactor));
    const offset = Math.floor(scale / 2) + 1;

    for (let r = offset; r <= nrows - offset; r += step) {
      for (let c = offset; c <= ncols - offset; c += step) {
        const q = classifyRegion(r, c, scale, pixels, ldim);
        if (q > 0.0) {
          detections.push([r, c, scale, q]);
        }
      }
    }

    scale = Math.floor(scale * scalefactor);
  }

  return detections;
}

export function cluster_detections(
  dets: number[][],
  iouThreshold: number
): number[][] {
  dets = dets.sort((a, b) => b[3] - a[3]);

  function calculate_iou(
    r1: number,
    c1: number,
    s1: number,
    r2: number,
    c2: number,
    s2: number
  ): number {
    const over_r = Math.max(0, Math.min(r1 + s1 / 2, r2 + s2 / 2) - Math.max(r1 - s1 / 2, r2 - s2 / 2));
    const over_c = Math.max(0, Math.min(c1 + s1 / 2, c2 + s2 / 2) - Math.max(c1 - s1 / 2, c2 - s2 / 2));
    const area1 = s1 * s1;
    const area2 = s2 * s2;
    const intersection = over_r * over_c;
    return intersection / (area1 + area2 - intersection);
  }

  const assignments = new Array(dets.length).fill(0);
  const clusters: number[][] = [];

  for (let i = 0; i < dets.length; ++i) {
    if (assignments[i] === 0) {
      let r = 0, c = 0, s = 0, q = 0, n = 0;
      for (let j = i; j < dets.length; ++j) {
        if (calculate_iou(dets[i][0], dets[i][1], dets[i][2], dets[j][0], dets[j][1], dets[j][2]) > iouThreshold) {
          assignments[j] = 1;
          r += dets[j][0];
          c += dets[j][1];
          s += dets[j][2];
          q += dets[j][3];
          n += 1;
        }
      }
      clusters.push([r / n, c / n, s / n, q]);
    }
  }

  return clusters;
}

export function rgba_to_grayscale(rgba: Uint8ClampedArray, nrows: number, ncols: number): Uint8Array {
  const gray = new Uint8Array(nrows * ncols);
  for (let r = 0; r < nrows; ++r) {
    for (let c = 0; c < ncols; ++c) {
      const idx = (r * ncols + c) * 4;
      gray[r * ncols + c] = Math.floor((2 * rgba[idx] + 7 * rgba[idx + 1] + 1 * rgba[idx + 2]) / 10);
    }
  }
  return gray;
}

