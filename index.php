<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>I Love You</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html, body {
      overflow: hidden;
      background-color: #000 !important;
      height: 100%;
      width: 100%;
    }
    body {
      -webkit-font-smoothing: antialiased;
    }
    .webgl {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      outline: none;
    }
    button {
      position: absolute;
      left: 50%;
      top: 80%;
      transform: translate(-50%, -50%);
      height: 12vh;
      width: 12vh;
      background: transparent;
      color: white;
      border: none;
      cursor: pointer;
      opacity: 1;
    }
    svg {
      width: 3.5vh;
      fill: currentColor;
    }
  </style>
</head>
<body translate="no">
  <canvas class="webgl"></canvas>
  <button id="play-music" type="button" aria-label="Play music">
    <svg viewBox="0 0 512 512" width="100" title="music">
      <path d="M470.38 1.51L150.41 96A32 32 0 0 0 128 126.51v261.41A139 139 0 0 0 96 384c-53 0-96 28.66-96 64s43 64 96 64 96-28.66 96-64V214.32l256-75v184.61a138.4 138.4 0 0 0-32-3.93c-53 0-96 28.66-96 64s43 64 96 64 96-28.65 96-64V32a32 32 0 0 0-41.62-30.49z" />
    </svg>
  </button>

  <script type="x-shader/x-vertex" id="vertexShader">
    #define M_PI 3.1415926535897932384626433832795
    uniform float uTime;
    uniform float uSize;
    attribute float aScale;
    attribute vec3 aColor;
    attribute float random;
    attribute float random1;
    attribute float aSpeed;
    varying vec3 vColor;
    varying vec2 vUv;
    void main() {
      float sign = 2.0 * (step(random, 0.5) - 0.5);
      float t = sign * mod(-uTime * aSpeed * 0.005 + 10.0 * aSpeed * aSpeed, M_PI);
      float a = pow(t, 2.0) * pow((t - sign * M_PI), 2.0);
      float radius = 0.14;
      vec3 myOffset = vec3(
        radius * 16.0 * pow(sin(t), 2.0) * sin(t),
        radius * (13.0 * cos(t) - 5.0 * cos(2.0 * t) - 2.0 * cos(3.0 * t) - cos(4.0 * t)),
        0.15 * (a * (random1 - 0.5)) * sin(abs(10.0 * (sin(0.2 * uTime + 0.2 * random))) * t)
      );
      vec4 modelPosition = modelMatrix * vec4(myOffset, 1.0);
      vec4 viewPosition = viewMatrix * modelPosition;
      viewPosition.xyz += position * aScale * uSize * pow(a, 0.5) * 0.5;
      gl_Position = projectionMatrix * viewPosition;
      vColor = aColor;
      vUv = uv;
    }
  </script>

  <script type="x-shader/x-fragment" id="fragmentShader">
    varying vec3 vColor;
    varying vec2 vUv;
    void main() {
      vec2 uv = vUv;
      vec3 color = vColor;
      float strength = distance(uv, vec2(0.5));
      strength *= 2.0;
      strength = 1.0 - strength;
      gl_FragColor = vec4(strength * color, 1.0);
    }
  </script>

  <script type="x-shader/x-vertex" id="vertexShader1">
    #define M_PI 3.1415926535897932384626433832795
    uniform float uTime;
    uniform float uSize;
    attribute float aScale;
    attribute vec3 aColor;
    attribute float phi;
    attribute float random;
    attribute float random1;
    varying vec3 vColor;
    varying vec2 vUv;
    void main() {
      float t = mod((-uTime + 100.0) * 0.06 * random1 + random * 2.0 * M_PI, 2.0 * M_PI);
      float angle = phi;
      vec3 myOffset = vec3(5.85 * cos(angle * t), 2.0 * (t - M_PI), 3.0 * sin(angle * t / t));
      vec4 modelPosition = modelMatrix * vec4(myOffset, 1.0);
      vec4 viewPosition = viewMatrix * modelPosition;
      viewPosition.xyz += position * aScale * uSize;
      gl_Position = projectionMatrix * viewPosition;
      vColor = aColor;
      vUv = uv;
    }
  </script>

  <script type="x-shader/x-fragment" id="fragmentShader1">
    uniform sampler2D uTex;
    varying vec3 vColor;
    varying vec2 vUv;
    void main() {
      vec2 uv = vUv;
      vec3 color = vColor;
      float strength = distance(uv, vec2(0.5, 0.65));
      strength *= 2.0;
      strength = 1.0 - strength;
      vec3 texture = texture2D(uTex, uv).rgb;
      gl_FragColor = vec4(texture * color * (strength + 0.3), 1.0);
    }
  </script>

  <script type="module">
    import * as THREE from "https://cdn.skypack.dev/three@0.135.0";
    import { gsap } from "https://cdn.skypack.dev/gsap@3.8.0";
    import { GLTFLoader } from "https://cdn.skypack.dev/three@0.135.0/examples/jsm/loaders/GLTFLoader";

    class World {
      constructor({ canvas, width, height, cameraPosition, fieldOfView = 75, nearPlane = 0.1, farPlane = 100 }) {
        this.parameters = { count: 1500, max: 12.5 * Math.PI, a: 2, c: 4.5 };
        this.textureLoader = new THREE.TextureLoader();
        this.scene = new THREE.Scene();
        this.scene.background = new THREE.Color(0x16000a);
        this.clock = new THREE.Clock();
        this.data = 0;
        this.time = { current: 0, t0: 0, t1: 0, t: 0, frequency: 0.0005 };
        this.angle = { x: 0, z: 0 };
        this.width = width || window.innerWidth;
        this.height = height || window.innerHeight;
        this.aspectRatio = this.width / this.height;
        this.fieldOfView = fieldOfView;
        this.camera = new THREE.PerspectiveCamera(fieldOfView, this.aspectRatio, nearPlane, farPlane);
        this.camera.position.set(cameraPosition.x, cameraPosition.y, cameraPosition.z);
        this.scene.add(this.camera);
        this.renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.setSize(this.width, this.height);
        this.addToScene();
        this.addButton();
        this.listenToResize();
        this.listenToMouseMove();
        this.loop();
      }

      render() {
        this.renderer.render(this.scene, this.camera);
      }

      loop() {
        this.time.elapsed = this.clock.getElapsedTime();
        this.time.delta = Math.min(60, (this.time.elapsed - this.time.current) * 1000);
        if (this.analyser && this.isRunning) {
          this.time.t = this.time.elapsed - this.time.t0 + this.time.t1;
          this.data = this.analyser.getAverageFrequency();
          this.data *= this.data / 2000;
          this.angle.x += this.time.delta * 0.001 * 0.63;
          this.angle.z += this.time.delta * 0.001 * 0.39;
          const justFinished = this.isRunning && !this.sound.isPlaying;
          if (justFinished) {
            this.time.t1 = this.time.t;
            this.audioBtn.disabled = false;
            this.isRunning = false;
            const tl = gsap.timeline();
            this.angle.x = 0;
            this.angle.z = 0;
            tl.to(this.camera.position, { x: 0, z: 4.5, duration: 4, ease: "expo.in" });
            tl.to(this.audioBtn, { opacity: 1, duration: 1, ease: "power1.out" });
          } else {
            this.camera.position.x = Math.sin(this.angle.x) * this.parameters.a;
            this.camera.position.z = Math.min(Math.max(Math.cos(this.angle.z) * this.parameters.c, 1.75), 6.5);
          }
        }
        this.camera.lookAt(this.scene.position);
        if (this.heartMaterial) {
          this.heartMaterial.uniforms.uTime.value += this.time.delta * this.time.frequency * (1 + this.data * 0.2);
        }
        if (this.model) {
          this.model.rotation.y -= 0.0005 * this.time.delta * (1 + this.data);
        }
        if (this.snowMaterial) {
          this.snowMaterial.uniforms.uTime.value += this.time.delta * 0.0004 * (1 + this.data);
        }
        this.render();
        this.time.current = this.time.elapsed;
        requestAnimationFrame(this.loop.bind(this));
      }

      listenToResize() {
        window.addEventListener("resize", () => {
          this.width = window.innerWidth;
          this.height = window.innerHeight;
          this.camera.aspect = this.width / this.height;
          this.camera.updateProjectionMatrix();
          this.renderer.setSize(this.width, this.height);
        });
      }

      listenToMouseMove() {
        window.addEventListener("mousemove", e => {
          const x = e.clientX;
          const y = e.clientY;
          gsap.to(this.camera.position, {
            x: gsap.utils.mapRange(0, window.innerWidth, 0.2, -0.2, x),
            y: gsap.utils.mapRange(0, window.innerHeight, 0.2, -0.2, -y)
          });
        });
      }

      addHeart() {
        this.heartMaterial = new THREE.ShaderMaterial({
          fragmentShader: document.getElementById("fragmentShader").textContent,
          vertexShader: document.getElementById("vertexShader").textContent,
          uniforms: {
            uTime: { value: 0 },
            uSize: { value: 0.2 },
            uTex: { value: this.textureLoader.load("https://assets.codepen.io/74321/heart.png") }
          },
          depthWrite: false,
          blending: THREE.AdditiveBlending,
          transparent: true
        });
        const count = this.parameters.count;
        const scales = new Float32Array(count);
        const colors = new Float32Array(count * 3);
        const speeds = new Float32Array(count);
        const randoms = new Float32Array(count);
        const randoms1 = new Float32Array(count);
        const colorChoices = ["white", "red", "pink", "crimson", "hotpink", "green", "aquamarine", "blue"];
        const squareGeometry = new THREE.PlaneGeometry(1, 1);
        this.instancedGeometry = new THREE.InstancedBufferGeometry();
        Object.keys(squareGeometry.attributes).forEach(attr => {
          this.instancedGeometry.attributes[attr] = squareGeometry.attributes[attr];
        });
        this.instancedGeometry.index = squareGeometry.index;
        this.instancedGeometry.maxInstancedCount = count;
        for (let i = 0; i < count; i++) {
          const i3 = 3 * i;
          randoms[i] = Math.random();
          randoms1[i] = Math.random();
          scales[i] = Math.random() * 0.35;
          const colorIndex = Math.floor(Math.random() * colorChoices.length);
          const color = new THREE.Color(colorChoices[colorIndex]);
          colors[i3 + 0] = color.r;
          colors[i3 + 1] = color.g;
          colors[i3 + 2] = color.b;
          speeds[i] = Math.random() * this.parameters.max;
        }
        this.instancedGeometry.setAttribute("random", new THREE.InstancedBufferAttribute(randoms, 1, false));
        this.instancedGeometry.setAttribute("random1", new THREE.InstancedBufferAttribute(randoms1, 1, false));
        this.instancedGeometry.setAttribute("aScale", new THREE.InstancedBufferAttribute(scales, 1, false));
        this.instancedGeometry.setAttribute("aSpeed", new THREE.InstancedBufferAttribute(speeds, 1, false));
        this.instancedGeometry.setAttribute("aColor", new THREE.InstancedBufferAttribute(colors, 3, false));
        this.heart = new THREE.Mesh(this.instancedGeometry, this.heartMaterial);
        this.scene.add(this.heart);
      }

      async addModel() {
        try {
          this.model = await this.loadObj("https://assets.codepen.io/74321/heart.glb");
          this.model.scale.set(0.01, 0.01, 0.01);
          this.model.material = new THREE.MeshMatcapMaterial({
            matcap: this.textureLoader.load("https://assets.codepen.io/74321/3.png", () => {
              gsap.to(this.model.scale, {
                x: 0.35,
                y: 0.35,
                z: 0.35,
                duration: 1.5,
                ease: "elastic.out(1, 0.3)",
                onComplete: () => {
                  gsap.to(this.model.scale, {
                    x: 0.4,
                    y: 0.4,
                    z: 0.4,
                    duration: 0.7,
                    yoyo: true,
                    repeat: -1,
                    ease: "sine.inOut"
                  });
                }
              });
            }),
            color: new THREE.Color("#ffffff")
          });
          this.scene.add(this.model);
        } catch (error) {
          console.error("Failed to load heart model:", error);
        }
      }

      addButton() {
        this.audioBtn = document.querySelector("#play-music");
        this.audioBtn.addEventListener("click", () => {
          this.audioBtn.disabled = true;
          if (this.analyser) {
            this.sound.play();
            this.time.t0 = this.time.elapsed;
            this.data = 0;
            this.isRunning = true;
            gsap.to(this.audioBtn, { opacity: 0, duration: 1, ease: "power1.out" });
          } else {
            this.loadMusic().then(() => {
              console.log("Music loaded successfully");
            }).catch(error => {
              console.error("Failed to load music:", error);
              this.audioBtn.disabled = false;
            });
          }
        });
      }

      loadObj(path) {
        const loader = new GLTFLoader();
        return new Promise((resolve, reject) => {
          loader.load(path, response => {
            resolve(response.scene.children[0]);
          }, undefined, error => {
            reject(error);
          });
        });
      }

      loadMusic() {
        return new Promise((resolve, reject) => {
          const listener = new THREE.AudioListener();
          this.camera.add(listener);
          this.sound = new THREE.Audio(listener);
          const audioLoader = new THREE.AudioLoader();
          audioLoader.load(
            "https://res.cloudinary.com/dmnxeusyw/video/upload/v1668310333/sharecs.net/music_ji3iak.mp3",
            buffer => {
              this.sound.setBuffer(buffer);
              this.sound.setLoop(false);
              this.sound.setVolume(0.5);
              this.sound.play();
              this.analyser = new THREE.AudioAnalyser(this.sound, 32);
              this.isRunning = true;
              this.time.t0 = this.time.elapsed;
              resolve(this.analyser.getAverageFrequency());
            },
            progress => {
              gsap.to(this.audioBtn, {
                opacity: () => 1 - progress.loaded / progress.total,
                duration: 1,
                ease: "power1.out"
              });
            },
            error => {
              reject(error);
            }
          );
        });
      }

      addSnow() {
        this.snowMaterial = new THREE.ShaderMaterial({
          fragmentShader: document.getElementById("fragmentShader1").textContent,
          vertexShader: document.getElementById("vertexShader1").textContent,
          uniforms: {
            uTime: { value: 0 },
            uSize: { value: 0.3 },
            uTex: { value: this.textureLoader.load("https://assets.codepen.io/74321/heart.png") }
          },
          depthWrite: false,
          blending: THREE.AdditiveBlending,
          transparent: true
        });
        const count = 550;
        const scales = new Float32Array(count);
        const colors = new Float32Array(count * 3);
        const phis = new Float32Array(count);
        const randoms = new Float32Array(count);
        const randoms1 = new Float32Array(count);
        const colorChoices = ["red", "pink", "hotpink", "green", "aquamarine", "blue"];
        const squareGeometry = new THREE.PlaneGeometry(1, 1);
        this.instancedGeometry = new THREE.InstancedBufferGeometry();
        Object.keys(squareGeometry.attributes).forEach(attr => {
          this.instancedGeometry.attributes[attr] = squareGeometry.attributes[attr];
        });
        this.instancedGeometry.index = squareGeometry.index;
        this.instancedGeometry.maxInstancedCount = count;
        for (let i = 0; i < count; i++) {
          const i3 = 3 * i;
          phis[i] = (Math.random() - 0.5) * 10;
          randoms[i] = Math.random();
          randoms1[i] = Math.random();
          scales[i] = Math.random() * 0.35;
          const colorIndex = Math.floor(Math.random() * colorChoices.length);
          const color = new THREE.Color(colorChoices[colorIndex]);
          colors[i3 + 0] = color.r;
          colors[i3 + 1] = color.g;
          colors[i3 + 2] = color.b;
        }
        this.instancedGeometry.setAttribute("phi", new THREE.InstancedBufferAttribute(phis, 1, false));
        this.instancedGeometry.setAttribute("random", new THREE.InstancedBufferAttribute(randoms, 1, false));
        this.instancedGeometry.setAttribute("random1", new THREE.InstancedBufferAttribute(randoms1, 1, false));
        this.instancedGeometry.setAttribute("aScale", new THREE.InstancedBufferAttribute(scales, 1, false));
        this.instancedGeometry.setAttribute("aColor", new THREE.InstancedBufferAttribute(colors, 3, false));
        this.snow = new THREE.Mesh(this.instancedGeometry, this.snowMaterial);
        this.scene.add(this.snow);
      }

      addToScene() {
        this.addModel();
        this.addHeart();
        this.addSnow();
      }
    }

    const world = new World({
      canvas: document.querySelector("canvas.webgl"),
      cameraPosition: { x: 0, y: 0, z: 4.5 }
    });
  </script>
</body>
</html>
