import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';

const GOLD = 0xc4a46a;
const EMERALD = 0x2a9a7c;
const NIGHT = 0x0b1412;

function makeFacadeMaps({ cols = 6, rows = 14, warm = true } = {}) {
  const w = 256;
  const h = 512;
  const colorCanvas = document.createElement('canvas');
  colorCanvas.width = w;
  colorCanvas.height = h;
  const ctx = colorCanvas.getContext('2d');

  const grad = ctx.createLinearGradient(0, 0, 0, h);
  grad.addColorStop(0, '#1a2f28');
  grad.addColorStop(1, '#0d1815');
  ctx.fillStyle = grad;
  ctx.fillRect(0, 0, w, h);

  // structural mullions
  ctx.strokeStyle = 'rgba(196, 164, 106, 0.35)';
  ctx.lineWidth = 2;
  for (let i = 1; i < cols; i += 1) {
    const x = (i / cols) * w;
    ctx.beginPath();
    ctx.moveTo(x, 0);
    ctx.lineTo(x, h);
    ctx.stroke();
  }

  const emissiveCanvas = document.createElement('canvas');
  emissiveCanvas.width = w;
  emissiveCanvas.height = h;
  const ectx = emissiveCanvas.getContext('2d');
  ectx.fillStyle = '#000';
  ectx.fillRect(0, 0, w, h);

  const padX = 14;
  const padY = 22;
  const gapX = 8;
  const gapY = 10;
  const cellW = (w - padX * 2 - gapX * (cols - 1)) / cols;
  const cellH = (h - padY * 2 - gapY * (rows - 1)) / rows;

  for (let r = 0; r < rows; r += 1) {
    for (let c = 0; c < cols; c += 1) {
      const lit = Math.random() > 0.22;
      const x = padX + c * (cellW + gapX);
      const y = padY + r * (cellH + gapY);

      if (lit) {
        const tone = warm
          ? (Math.random() > 0.55 ? '#ffe6a8' : '#c4a46a')
          : (Math.random() > 0.5 ? '#9af0d4' : '#5cc4a0');
        ctx.fillStyle = tone;
        ctx.fillRect(x, y, cellW, cellH);
        ectx.fillStyle = tone;
        ectx.fillRect(x, y, cellW, cellH);
        // soft glow bleed
        ectx.fillStyle = warm ? 'rgba(255, 210, 120, 0.35)' : 'rgba(100, 220, 180, 0.3)';
        ectx.fillRect(x - 2, y - 2, cellW + 4, cellH + 4);
      } else {
        ctx.fillStyle = 'rgba(120, 160, 150, 0.22)';
        ctx.fillRect(x, y, cellW, cellH);
      }
    }
  }

  // gold crown band at top of texture
  ctx.fillStyle = 'rgba(196, 164, 106, 0.55)';
  ctx.fillRect(0, 0, w, 10);

  const map = new THREE.CanvasTexture(colorCanvas);
  map.colorSpace = THREE.SRGBColorSpace;
  map.anisotropy = 8;

  const emissiveMap = new THREE.CanvasTexture(emissiveCanvas);
  emissiveMap.colorSpace = THREE.SRGBColorSpace;

  return { map, emissiveMap };
}

function facadeMaterial(opts = {}) {
  const { map, emissiveMap } = makeFacadeMaps(opts);
  return new THREE.MeshStandardMaterial({
    map,
    emissiveMap,
    emissive: new THREE.Color(0xffffff),
    emissiveIntensity: 1.15,
    roughness: 0.38,
    metalness: 0.62,
    color: 0xffffff,
  });
}

function solidMaterial(hex, metal = 0.7) {
  return new THREE.MeshStandardMaterial({
    color: hex,
    roughness: 0.3,
    metalness: metal,
    emissive: hex,
    emissiveIntensity: 0.12,
  });
}

function addFloorPlate(group, y, w, d, color = GOLD) {
  const plate = new THREE.Mesh(
    new THREE.BoxGeometry(w * 1.12, 0.08, d * 1.12),
    solidMaterial(color, 0.85)
  );
  plate.position.y = y;
  group.add(plate);
}

function addTower(group, {
  x = 0,
  z = 0,
  w = 1.25,
  d = 1.25,
  h = 4.8,
  warm = true,
  accent = GOLD,
} = {}) {
  const rows = Math.max(8, Math.round(h * 3));
  const mat = facadeMaterial({ cols: 5, rows, warm });
  const mesh = new THREE.Mesh(new THREE.BoxGeometry(w, h, d), mat);
  mesh.position.set(x, h / 2, z);
  mesh.castShadow = true;
  mesh.receiveShadow = true;
  group.add(mesh);

  // mid setback / balcony band
  const band = new THREE.Mesh(
    new THREE.BoxGeometry(w * 1.14, 0.14, d * 1.14),
    solidMaterial(accent, 0.9)
  );
  band.position.set(x, h * 0.55, z);
  group.add(band);

  addFloorPlate(group, h + 0.04, w, d, accent);

  const spire = new THREE.Mesh(
    new THREE.CylinderGeometry(0.035, 0.06, 0.7, 10),
    solidMaterial(accent, 0.95)
  );
  spire.position.set(x, h + 0.42, z);
  group.add(spire);

  const orb = new THREE.Mesh(
    new THREE.SphereGeometry(0.09, 16, 16),
    solidMaterial(warm ? GOLD : EMERALD, 0.95)
  );
  orb.position.set(x, h + 0.78, z);
  group.add(orb);

  return mesh;
}

function makeCurtainWallMaps() {
  const w = 512;
  const h = 1024;
  const color = document.createElement('canvas');
  const emissive = document.createElement('canvas');
  color.width = emissive.width = w;
  color.height = emissive.height = h;
  const ctx = color.getContext('2d');
  const ectx = emissive.getContext('2d');

  // cool reflective glass base
  const base = ctx.createLinearGradient(0, 0, w, 0);
  base.addColorStop(0, '#0a1412');
  base.addColorStop(0.35, '#14302a');
  base.addColorStop(0.55, '#1a3a34');
  base.addColorStop(1, '#0b1513');
  ctx.fillStyle = base;
  ctx.fillRect(0, 0, w, h);

  // sky reflection streak
  const sky = ctx.createLinearGradient(0, 0, 0, h);
  sky.addColorStop(0, 'rgba(180, 210, 220, 0.14)');
  sky.addColorStop(0.35, 'rgba(120, 160, 170, 0.05)');
  sky.addColorStop(1, 'rgba(0, 0, 0, 0)');
  ctx.fillStyle = sky;
  ctx.fillRect(0, 0, w * 0.45, h);

  ectx.fillStyle = '#000';
  ectx.fillRect(0, 0, w, h);

  const cols = 10;
  const rows = 36;
  const frame = 3;
  const pad = 8;
  const cellW = (w - pad * 2) / cols;
  const cellH = (h - pad * 2) / rows;

  for (let r = 0; r < rows; r += 1) {
    for (let c = 0; c < cols; c += 1) {
      const x = pad + c * cellW;
      const y = pad + r * cellH;
      // aluminum frame
      ctx.fillStyle = 'rgba(170, 175, 172, 0.28)';
      ctx.fillRect(x, y, cellW, cellH);

      const ix = x + frame;
      const iy = y + frame;
      const iw = cellW - frame * 2;
      const ih = cellH - frame * 2;

      const lit = Math.random() > 0.42;
      if (lit) {
        const warm = Math.random();
        const tone = warm > 0.62
          ? `rgba(255, 214, 150, ${0.35 + Math.random() * 0.35})`
          : warm > 0.3
            ? `rgba(210, 230, 235, ${0.2 + Math.random() * 0.25})`
            : `rgba(160, 200, 190, ${0.18 + Math.random() * 0.2})`;
        ctx.fillStyle = tone;
        ctx.fillRect(ix, iy, iw, ih);
        ectx.fillStyle = tone;
        ectx.fillRect(ix, iy, iw, ih);
      } else {
        // empty glass pane with subtle tint
        const shade = 0.08 + Math.random() * 0.1;
        ctx.fillStyle = `rgba(90, 130, 125, ${shade})`;
        ctx.fillRect(ix, iy, iw, ih);
        // specular glint
        if (Math.random() > 0.7) {
          ctx.fillStyle = 'rgba(230, 240, 245, 0.12)';
          ctx.fillRect(ix, iy, iw * 0.35, ih);
        }
      }
    }
    // floor slab line
    ctx.fillStyle = 'rgba(40, 48, 46, 0.55)';
    ctx.fillRect(pad, pad + (r + 1) * cellH - 1, w - pad * 2, 2);
  }

  const map = new THREE.CanvasTexture(color);
  map.colorSpace = THREE.SRGBColorSpace;
  map.anisotropy = 4;
  map.wrapS = map.wrapT = THREE.RepeatWrapping;

  const emissiveMap = new THREE.CanvasTexture(emissive);
  emissiveMap.colorSpace = THREE.SRGBColorSpace;
  emissiveMap.wrapS = emissiveMap.wrapT = THREE.RepeatWrapping;

  return { map, emissiveMap };
}

function curtainFacadeMaterial(repeatY = 1.8) {
  const { map, emissiveMap } = makeCurtainWallMaps();
  map.repeat.set(1, repeatY);
  emissiveMap.repeat.set(1, repeatY);
  return new THREE.MeshStandardMaterial({
    map,
    emissiveMap,
    emissive: new THREE.Color(0xffffff),
    emissiveIntensity: 0.75,
    color: 0xffffff,
    roughness: 0.22,
    metalness: 0.72,
  });
}

/** Realistic Manara landmark tower */
function addLighthouse(group) {
  const facade = curtainFacadeMaterial(2.1);
  const facadeShort = curtainFacadeMaterial(1.35);
  const cladding = new THREE.MeshStandardMaterial({
    color: 0x2a3330,
    roughness: 0.55,
    metalness: 0.35,
  });
  const stone = new THREE.MeshStandardMaterial({
    color: 0x1a221f,
    roughness: 0.82,
    metalness: 0.12,
  });
  const bronze = new THREE.MeshStandardMaterial({
    color: 0xa8895a,
    roughness: 0.38,
    metalness: 0.88,
  });
  const concrete = new THREE.MeshStandardMaterial({
    color: 0x3a423e,
    roughness: 0.9,
    metalness: 0.05,
  });

  // ground disc (subtle)
  const ground = new THREE.Mesh(
    new THREE.CircleGeometry(6, 32),
    new THREE.MeshStandardMaterial({ color: 0x08100e, roughness: 0.95, metalness: 0.05, transparent: true, opacity: 0.65 })
  );
  ground.rotation.x = -Math.PI / 2;
  group.add(ground);

  // multi-step plaza
  const plaza = new THREE.Mesh(new THREE.CylinderGeometry(3.0, 3.25, 0.18, 32), stone);
  plaza.position.y = 0.09;
  group.add(plaza);
  const step = new THREE.Mesh(new THREE.BoxGeometry(3.1, 0.14, 2.6), concrete);
  step.position.y = 0.28;
  group.add(step);

  // podium with glass lobby strip
  const podium = new THREE.Mesh(new THREE.BoxGeometry(2.7, 1.05, 2.3), stone);
  podium.position.y = 0.85;
  group.add(podium);
  const lobby = new THREE.Mesh(
    new THREE.BoxGeometry(2.45, 0.55, 0.08),
    new THREE.MeshStandardMaterial({
      color: 0xd8efe8,
      emissive: 0x7ecfb8,
      emissiveIntensity: 0.35,
      roughness: 0.15,
      metalness: 0.4,
      transparent: true,
      opacity: 0.85,
    })
  );
  lobby.position.set(0, 0.75, 1.18);
  group.add(lobby);
  const podiumCap = new THREE.Mesh(new THREE.BoxGeometry(2.85, 0.1, 2.45), bronze);
  podiumCap.position.y = 1.4;
  group.add(podiumCap);

  // helper: box with facade on +Z/-Z and cladding on sides
  const towerBox = (w, h, d, y, matFront) => {
    const geo = new THREE.BoxGeometry(w, h, d);
    const side = cladding;
    const top = bronze;
    const mats = [side, side, top, stone, matFront, matFront];
    const mesh = new THREE.Mesh(geo, mats);
    mesh.position.y = y;
    group.add(mesh);
    return mesh;
  };

  // main shaft
  towerBox(1.4, 6.6, 1.25, 4.75, facade);

  // recessed horizontal floor belts
  [2.85, 4.55, 6.25, 7.85].forEach((y) => {
    const belt = new THREE.Mesh(new THREE.BoxGeometry(1.52, 0.1, 1.38), cladding);
    belt.position.y = y;
    group.add(belt);
  });

  // slim bronze vertical fins (inset slightly)
  [-0.74, 0.74].forEach((x) => {
    const fin = new THREE.Mesh(new THREE.BoxGeometry(0.045, 6.5, 1.32), bronze);
    fin.position.set(x, 4.75, 0);
    group.add(fin);
  });

  // secondary tower volume
  const wing = towerBox(0.95, 4.2, 1.05, 3.55, facadeShort);
  wing.position.x = 1.25;
  wing.position.z = 0.2;

  const wingBelt = new THREE.Mesh(new THREE.BoxGeometry(1.05, 0.1, 1.15), cladding);
  wingBelt.position.set(1.25, 5.55, 0.2);
  group.add(wingBelt);

  // mechanical crown
  const crownBase = new THREE.Mesh(new THREE.BoxGeometry(1.55, 0.45, 1.4), cladding);
  crownBase.position.y = 8.25;
  group.add(crownBase);
  const crownGlass = new THREE.Mesh(
    new THREE.BoxGeometry(1.35, 0.55, 1.2),
    new THREE.MeshStandardMaterial({
      color: 0xcfd9d6,
      emissive: 0xffe0b0,
      emissiveIntensity: 0.25,
      roughness: 0.18,
      metalness: 0.55,
      transparent: true,
      opacity: 0.88,
    })
  );
  crownGlass.position.y = 8.75;
  group.add(crownGlass);

  const crownRail = new THREE.Mesh(new THREE.BoxGeometry(1.65, 0.06, 1.5), bronze);
  crownRail.position.y = 9.08;
  group.add(crownRail);

  // beacon core (brand tip — subtle)
  const lampCore = new THREE.Mesh(
    new THREE.SphereGeometry(0.1, 12, 12),
    new THREE.MeshStandardMaterial({
      color: 0xfff5e0,
      emissive: 0xffe0a8,
      emissiveIntensity: 1.6,
      roughness: 0.35,
      metalness: 0,
    })
  );
  lampCore.position.y = 9.35;
  lampCore.name = 'beaconCore';
  group.add(lampCore);

  const spire = new THREE.Mesh(new THREE.CylinderGeometry(0.015, 0.04, 0.55, 10), bronze);
  spire.position.y = 9.7;
  group.add(spire);
  const tip = new THREE.Mesh(new THREE.SphereGeometry(0.045, 10, 10), bronze);
  tip.position.y = 10.0;
  group.add(tip);

  // soft ambient beacon pivot (no cartoon beam)
  const beamPivot = new THREE.Group();
  beamPivot.name = 'beaconPivot';
  beamPivot.position.y = 9.35;
  group.add(beamPivot);

  const aura = new THREE.PointLight(0xffe2b0, 6, 10, 2);
  aura.position.set(0, 9.35, 0);
  aura.name = 'beaconAura';
  group.add(aura);

  // contact shadow
  const shadow = new THREE.Mesh(
    new THREE.CircleGeometry(2.4, 24),
    new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.32, depthWrite: false })
  );
  shadow.rotation.x = -Math.PI / 2;
  shadow.position.y = 0.02;
  group.add(shadow);
}

function addVilla(group) {
  const mat = facadeMaterial({ cols: 4, rows: 5, warm: true });
  const body = new THREE.Mesh(new THREE.BoxGeometry(2.6, 1.45, 1.9), mat);
  body.position.y = 0.85;
  body.castShadow = true;
  group.add(body);

  const wingMat = facadeMaterial({ cols: 3, rows: 4, warm: false });
  const wing = new THREE.Mesh(new THREE.BoxGeometry(1.35, 1.1, 1.5), wingMat);
  wing.position.set(-1.55, 0.65, 0.15);
  wing.castShadow = true;
  group.add(wing);

  const roof = new THREE.Mesh(
    new THREE.ConeGeometry(1.7, 0.75, 4),
    solidMaterial(GOLD, 0.88)
  );
  roof.rotation.y = Math.PI / 4;
  roof.position.set(0.1, 1.9, 0);
  group.add(roof);

  const terrace = new THREE.Mesh(
    new THREE.BoxGeometry(1.4, 0.1, 0.9),
    solidMaterial(0x1e332c, 0.5)
  );
  terrace.position.set(1.5, 0.12, 0.85);
  group.add(terrace);

  const pool = new THREE.Mesh(
    new THREE.BoxGeometry(1.15, 0.07, 0.72),
    new THREE.MeshPhysicalMaterial({
      color: 0x7fd4c8,
      roughness: 0.08,
      metalness: 0.15,
      transmission: 0.45,
      transparent: true,
      opacity: 0.9,
    })
  );
  pool.position.set(1.5, 0.18, 0.85);
  group.add(pool);

  // columns
  for (const cx of [-0.7, 0.7]) {
    const col = new THREE.Mesh(
      new THREE.CylinderGeometry(0.07, 0.07, 1.35, 10),
      solidMaterial(GOLD, 0.9)
    );
    col.position.set(cx, 0.75, 1.05);
    group.add(col);
  }
}

function buildSceneVariant(variant) {
  const root = new THREE.Group();

  if (variant === 'lighthouse') {
    addLighthouse(root);
    return root;
  }

  if (variant === 'twin') {
    addTower(root, { x: -1.2, w: 1.2, d: 1.2, h: 5.0, warm: true, accent: GOLD });
    addTower(root, { x: 1.2, w: 1.1, d: 1.1, h: 3.9, warm: false, accent: EMERALD });
    const bridge = new THREE.Mesh(
      new THREE.BoxGeometry(1.7, 0.22, 0.5),
      solidMaterial(GOLD, 0.9)
    );
    bridge.position.set(0, 2.55, 0);
    root.add(bridge);
    const glass = new THREE.Mesh(
      new THREE.BoxGeometry(1.5, 0.55, 0.18),
      new THREE.MeshPhysicalMaterial({
        color: 0xa8d8cc,
        roughness: 0.1,
        metalness: 0.2,
        transmission: 0.4,
        transparent: true,
        opacity: 0.85,
      })
    );
    glass.position.set(0, 2.95, 0);
    root.add(glass);
  } else if (variant === 'villa') {
    addVilla(root);
  } else if (variant === 'complex') {
    addTower(root, { x: -1.7, z: 0.45, w: 1.0, d: 1.0, h: 3.5, warm: false, accent: EMERALD });
    addTower(root, { x: 0.05, z: -0.25, w: 1.35, d: 1.35, h: 5.4, warm: true, accent: GOLD });
    addTower(root, { x: 1.65, z: 0.55, w: 0.9, d: 0.9, h: 2.9, warm: false, accent: EMERALD });
    const podium = new THREE.Mesh(
      new THREE.BoxGeometry(4.4, 0.38, 2.5),
      solidMaterial(0x15221c, 0.55)
    );
    podium.position.y = 0.19;
    podium.receiveShadow = true;
    root.add(podium);
    addFloorPlate(root, 0.4, 4.0, 2.2, GOLD);
  } else {
    addTower(root, { w: 1.4, d: 1.4, h: 5.5, warm: true, accent: GOLD });
    const annex = new THREE.Mesh(
      new THREE.BoxGeometry(0.95, 2.4, 0.95),
      facadeMaterial({ cols: 3, rows: 8, warm: false })
    );
    annex.position.set(1.3, 1.2, 0.25);
    annex.castShadow = true;
    root.add(annex);
    addFloorPlate(root, 2.45, 0.95, 0.95, EMERALD);
  }

  const ground = new THREE.Mesh(
    new THREE.CircleGeometry(4.4, 64),
    new THREE.MeshStandardMaterial({
      color: 0x08110f,
      roughness: 0.55,
      metalness: 0.4,
    })
  );
  ground.rotation.x = -Math.PI / 2;
  ground.receiveShadow = true;
  root.add(ground);

  const ring = new THREE.Mesh(
    new THREE.TorusGeometry(2.75, 0.04, 16, 96),
    new THREE.MeshStandardMaterial({
      color: GOLD,
      emissive: GOLD,
      emissiveIntensity: 0.85,
      metalness: 0.95,
      roughness: 0.2,
    })
  );
  ring.rotation.x = Math.PI / 2;
  ring.position.y = 0.05;
  root.add(ring);

  const ring2 = new THREE.Mesh(
    new THREE.TorusGeometry(3.35, 0.018, 12, 80),
    new THREE.MeshStandardMaterial({
      color: EMERALD,
      emissive: EMERALD,
      emissiveIntensity: 0.55,
      metalness: 0.9,
      roughness: 0.25,
    })
  );
  ring2.rotation.x = Math.PI / 2;
  ring2.position.y = 0.03;
  root.add(ring2);

  return root;
}

function mountBuilding(el) {
  const variant = el.getAttribute('data-variant') || 'tower';
  const isHero = el.hasAttribute('data-hero-3d') || el.getAttribute('data-mode') === 'hero';
  const isBg = el.hasAttribute('data-bg-3d');
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const width = () => el.clientWidth || 320;
  const height = () => el.clientHeight || 320;

  const scene = new THREE.Scene();
  if (!isHero) scene.fog = new THREE.FogExp2(NIGHT, 0.038);

  const camera = new THREE.PerspectiveCamera(isHero ? 32 : 40, width() / height(), 0.1, 120);
  if (variant === 'lighthouse') {
    camera.position.set(isHero ? 8.4 : 5.2, isHero ? 4.6 : 4.0, isHero ? 14.2 : 8.0);
  } else {
    camera.position.set(4.6, 3.6, 5.8);
  }

  const renderer = new THREE.WebGLRenderer({
    antialias: !isHero,
    alpha: true,
    powerPreference: 'high-performance',
  });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, isHero ? 1.25 : 1.5));
  renderer.setSize(width(), height(), false);
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = isHero ? 1.12 : 1.25;
  renderer.shadowMap.enabled = !isHero;
  el.appendChild(renderer.domElement);
  renderer.domElement.style.width = '100%';
  renderer.domElement.style.height = '100%';
  renderer.domElement.style.display = 'block';
  const touchFriendly = window.matchMedia('(pointer: coarse)').matches
    || window.matchMedia('(max-width: 960px)').matches;
  const heroPassThrough = isHero && touchFriendly;
  renderer.domElement.style.touchAction = heroPassThrough || isBg ? 'pan-y' : 'none';
  renderer.domElement.setAttribute('aria-hidden', 'true');

  scene.add(new THREE.AmbientLight(0xb8c8c0, isHero ? 0.32 : 0.45));

  const key = new THREE.DirectionalLight(0xfff4e6, isHero ? 1.35 : 1.55);
  key.position.set(6.5, 12, 5);
  if (!isHero) {
    key.castShadow = true;
    key.shadow.mapSize.set(512, 512);
  }
  scene.add(key);

  const fill = new THREE.PointLight(0x7ecfb8, isHero ? 8 : 28, 20, 2);
  fill.position.set(-4, 5, 3);
  scene.add(fill);

  const rim = new THREE.PointLight(0xc4a46a, isHero ? 7 : 20, 16, 2);
  rim.position.set(3, 4, -3.5);
  scene.add(rim);

  const hemi = new THREE.HemisphereLight(0xd0e4e0, 0x0a1210, isHero ? 0.55 : 0.55);
  scene.add(hemi);

  if (isHero) {
    scene.fog = new THREE.FogExp2(0x060b10, 0.022);
  }

  const model = buildSceneVariant(variant);
  if (isHero && variant === 'lighthouse') {
    model.position.set(-2.1, -0.45, 0);
    model.scale.set(1.22, 1.28, 1.22);
  }
  scene.add(model);

  const beaconPivot = model.getObjectByName('beaconPivot');
  const beaconAura = model.getObjectByName('beaconAura');
  const beaconCore = model.getObjectByName('beaconCore');
  const sweepEl = null;
  const rootStyle = document.documentElement.style;

  const controls = new OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.08;
  controls.enablePan = false;
  controls.enableZoom = false;
  controls.enableRotate = !isBg && !heroPassThrough;
  controls.minPolarAngle = Math.PI * (variant === 'lighthouse' ? 0.3 : 0.26);
  controls.maxPolarAngle = Math.PI * (variant === 'lighthouse' ? 0.5 : 0.48);
  controls.autoRotate = !reduceMotion;
  controls.autoRotateSpeed = isBg ? 0.35 : (isHero ? 0.28 : 0.9);
  controls.target.set(
    isHero && variant === 'lighthouse' ? -1.4 : 0,
    variant === 'lighthouse' ? (isHero ? 3.9 : 3.2) : 1.75,
    0
  );
  controls.update();

  if (isBg || heroPassThrough) {
    renderer.domElement.style.pointerEvents = 'none';
    el.style.pointerEvents = 'none';
    el.style.touchAction = 'pan-y';
  }

  // جيروسكوب الموبايل — يحرّك البرج مع ميل الهاتف بدون منع السكرول
  const gyro = {
    enabled: false,
    ready: false,
    beta: 0,
    gamma: 0,
    baseBeta: null,
    baseGamma: null,
  };
  const onDeviceOrientation = (event) => {
    if (event.beta == null || event.gamma == null) return;
    if (gyro.baseBeta == null) {
      gyro.baseBeta = event.beta;
      gyro.baseGamma = event.gamma;
    }
    gyro.beta = Math.max(-22, Math.min(22, event.beta - gyro.baseBeta));
    gyro.gamma = Math.max(-22, Math.min(22, event.gamma - gyro.baseGamma));
    gyro.ready = true;
  };
  const enableGyro = async () => {
    if (gyro.enabled || reduceMotion || !isHero || !heroPassThrough) return;
    try {
      if (
        typeof DeviceOrientationEvent !== 'undefined'
        && typeof DeviceOrientationEvent.requestPermission === 'function'
      ) {
        const state = await DeviceOrientationEvent.requestPermission();
        if (state !== 'granted') return;
      }
      window.addEventListener('deviceorientation', onDeviceOrientation, { passive: true });
      gyro.enabled = true;
      controls.autoRotateSpeed = 0.08;
    } catch {
      // أجهزة بدون دعم — يبقى التدوير التلقائي
    }
  };
  if (isHero && heroPassThrough && !reduceMotion) {
    enableGyro();
    window.addEventListener('touchstart', () => { enableGyro(); }, { once: true, passive: true });
  }

  let active = false;
  let raf = 0;
  let spark = 0;

  let dragging = false;
  let sweepOn = false;
  let frame = 0;
  const syncPageSweep = () => {
    if (!isHero || !beaconPivot) return;
    const deg = ((beaconPivot.rotation.y * 180) / Math.PI) % 360;
    rootStyle.setProperty('--beacon-angle', `${deg}deg`);
    if (!sweepOn && sweepEl) {
      sweepEl.classList.add('is-on');
      sweepOn = true;
      rootStyle.setProperty('--beacon-x', '28%');
      rootStyle.setProperty('--beacon-y', '36%');
    }
  };

  const animate = () => {
    if (!active) return;
    frame += 1;
    spark += 0.016;
    if (beaconPivot && !reduceMotion) {
      beaconPivot.rotation.y = spark * 0.35;
    }
    if (gyro.ready && isHero) {
      const targetY = (gyro.gamma / 22) * 0.42;
      const targetX = (gyro.beta / 22) * 0.14;
      model.rotation.y += (targetY - model.rotation.y) * 0.1;
      model.rotation.x += (targetX - model.rotation.x) * 0.1;
    }
    const shouldRender = dragging || gyro.ready || frame % 2 === 0;
    if (shouldRender) {
      if (beaconAura) beaconAura.intensity = 9 + Math.sin(spark * 1.2) * 2;
      if (beaconCore?.material) beaconCore.material.emissiveIntensity = 1.7 + Math.sin(spark * 1.3) * 0.35;
      if (frame % 8 === 0) syncPageSweep();
      controls.update();
      renderer.render(scene, camera);
    }
    raf = requestAnimationFrame(animate);
  };

  const start = () => {
    if (active) return;
    active = true;
    renderer.domElement.style.visibility = 'visible';
    raf = requestAnimationFrame(animate);
  };

  const stop = () => {
    active = false;
    if (raf) cancelAnimationFrame(raf);
    raf = 0;
    renderer.domElement.style.visibility = 'hidden';
  };

  const onResize = () => {
    const w = width();
    const h = height();
    if (w < 2 || h < 2) return;
    camera.aspect = w / h;
    camera.updateProjectionMatrix();
    renderer.setSize(w, h, false);
  };

  const ro = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(onResize) : null;
  if (ro) ro.observe(el);
  else window.addEventListener('resize', onResize);

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting && entry.intersectionRatio > 0.05) start();
        else stop();
      });
    },
    { threshold: [0, 0.05, 0.15], rootMargin: '0px 0px -15% 0px' }
  );
  io.observe(el);

  controls.addEventListener('start', () => {
    dragging = true;
    controls.autoRotate = false;
    el.classList.add('is-dragging');
  });
  controls.addEventListener('end', () => {
    dragging = false;
    el.classList.remove('is-dragging');
    if (!reduceMotion) {
      window.setTimeout(() => {
        controls.autoRotate = true;
      }, 1600);
    }
  });

  return () => {
    stop();
    io.disconnect();
    if (ro) ro.disconnect();
    if (gyro.enabled) {
      window.removeEventListener('deviceorientation', onDeviceOrientation);
    }
    controls.dispose();
    renderer.dispose();
    scene.traverse((obj) => {
      if (obj.geometry) obj.geometry.dispose();
      if (obj.material) {
        const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
        mats.forEach((m) => {
          if (m.map) m.map.dispose();
          if (m.emissiveMap) m.emissiveMap.dispose();
          m.dispose();
        });
      }
    });
    if (renderer.domElement.parentNode === el) el.removeChild(renderer.domElement);
  };
}

export function initSite3d() {
  const hosts = document.querySelectorAll('[data-building-3d]');
  if (!hosts.length) return;

  hosts.forEach((el) => {
    try {
      mountBuilding(el);
    } catch (err) {
      console.warn('[site-3d]', err);
      el.classList.add('is-fallback');
    }
  });
}
