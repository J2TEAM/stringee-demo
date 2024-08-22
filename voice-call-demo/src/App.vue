<script setup>
import { ref } from "vue";
const loggedIn = ref(false);
const username = ref("");
const accessToken = ref("");
const callStatus = ref("");
const friendUsername = ref("");
const loading = ref(false);

const localVideo = ref(null);
const remoteVideo = ref(null);
const isVideoCall = ref(false);

const stringeeClient = new StringeeClient();
let call = null; // call handler
const hasIncomingCall = ref(false);
const isCalling = ref(false);

const friendName = ref("");

function settingCallEvent(call1) {
  call1.on("addremotestream", function (stream) {
    // reset srcObject to work around minor bugs in Chrome and Edge.
    console.log("addremotestream");
    if (remoteVideo.value) {
      remoteVideo.value.srcObject = null;
      remoteVideo.value.srcObject = stream;
    }
  });

  call1.on("addlocalstream", function (stream) {
    // reset srcObject to work around minor bugs in Chrome and Edge.
    console.log("addlocalstream");
    localVideo.value.srcObject = null;
    localVideo.value.srcObject = stream;
  });

  call1.on("signalingstate", function (state) {
    console.log("signalingstate ", state);

    callStatus.value = state.reason;

    if (state.code === 3) {
      // call accepted
      isCalling.value = true;
      loading.value = false;
    } else if (state.code === 4 || state.code === 5 || state.code === 6) {
      // call ended
      isCalling.value = false;
      loading.value = false;
      hasIncomingCall.value = false;
    }
  });

  call1.on("mediastate", function (state) {
    console.log("mediastate ", state);
  });

  call1.on("info", function (info) {
    console.log("on info:" + JSON.stringify(info));
  });
}

stringeeClient.on("connect", () => console.log("Connected to StringeeServer"));

stringeeClient.on("authen", (res) => {
  if (res.message === "SUCCESS") {
    loggedIn.value = true;
  }
});

stringeeClient.on("incomingcall", (incomingcall) => {
  console.log("incomingcall", incomingcall);
  call = incomingcall;
  settingCallEvent(incomingcall);
  hasIncomingCall.value = true;
  isVideoCall.value = incomingcall.isVideoCall;

  friendName.value = incomingcall.fromNumber;
  loading.value = true;
});

const onLogin = async () => {
  const res = await fetch(
    `${import.meta.env.VITE_JWT_ENDPOINT}?u=${username.value}`
  );

  const data = await res.json();

  accessToken.value = data.access_token;
  stringeeClient.connect(accessToken.value);
};

const onCall = async (videoCall = false) => {
  if (isCalling.value || !friendUsername.value) {
    return;
  }

  if (username.value === friendUsername.value) {
    alert("Không thể gọi cho chính mình");
    return;
  }

  loading.value = true;
  isVideoCall.value = videoCall;

  call = new StringeeCall(
    stringeeClient,
    username.value,
    friendUsername.value,
    videoCall
  );
  settingCallEvent(call);

  call.makeCall(function (res) {
    console.log("make call callback: " + JSON.stringify(res));

    friendName.value = res.toNumber;
  });
};

const acceptCall = () => {
  call.answer(function (res) {
    console.log("answer call callback: " + JSON.stringify(res));
    hasIncomingCall.value = false;
    isCalling.value = true;
    loading.value = false;
  });
};

const rejectCall = () => {
  call.reject(function (res) {
    console.log("reject call callback: " + JSON.stringify(res));
    hasIncomingCall.value = false;
    loading.value = false;
  });
};

const hangupCall = () => {
  call.hangup(function (res) {
    console.log("hangup call callback: " + JSON.stringify(res));
    isCalling.value = false;
    loading.value = false;
  });
};

const upgradeToVideoCall = () => {
  call.upgradeToVideoCall();
  isVideoCall.value = true;
};
</script>

<template>
  <div class="row">
    <div class="col">
      <h1>Demo: Voice Call & Video Call</h1>

      <p>
        Trạng thái:
        {{ loggedIn ? `đã đăng nhập (${username})` : "chưa đăng nhập" }}
      </p>

      <form @submit.prevent="onLogin" v-if="!loggedIn">
        <div class="mb-3">
          <label for="username" class="form-label">Tên đăng nhập</label>
          <input
            type="text"
            v-model="username"
            class="form-control"
            id="username"
            placeholder="Nhập tên đăng nhập"
            autofocus
            required
          />
        </div>
        <button type="submit" class="btn btn-primary">Đăng nhập</button>
      </form>

      <form action="#" @submit.prevent="onCall(false)" v-else>
        <div class="mb-3">
          <label for="friend-username" class="form-label"
            >Bạn muốn gọi cho ai?</label
          >
          <input
            type="text"
            v-model="friendUsername"
            class="form-control"
            id="friend-username"
            placeholder="Nhập ID bạn bè"
            :disabled="isCalling || loading"
            required
          />
        </div>

        <button
          type="submit"
          class="btn btn-primary"
          :disabled="loading || isCalling"
        >
          <i class="bi bi-telephone"></i>
          {{ loading ? "Đang gọi..." : "Gọi thoại" }}
        </button>

        <button
          type="button"
          class="btn btn-secondary ms-3"
          :disabled="loading || isCalling"
          @click="onCall(true)"
        >
          <i class="bi bi-camera-video"></i>
          {{ loading ? "Đang gọi..." : "Gọi video" }}
        </button>
      </form>

      <!-- <p class="mt-3" v-if="isCalling && callStatus">
        Trạng thái cuộc gọi: {{ callStatus }}
      </p> -->

      <div v-if="hasIncomingCall" class="mt-3">
        <p>
          Bạn nhận được cuộc gọi từ: <strong>{{ call.fromNumber }}</strong>
        </p>

        <button class="btn btn-primary me-3" @click="acceptCall">
          Trả lời
        </button>
        <button class="btn btn-danger" @click="rejectCall">Từ chối</button>
      </div>

      <div v-if="isCalling" class="mt-3">
        <p>
          Đang gọi cho: <strong>{{ friendName }}</strong>
        </p>

        <!-- <button
          v-if="!isVideoCall"
          class="btn btn-primary me-3"
          @click="upgradeToVideoCall"
        >
          <i class="bi bi-camera-video"></i> Chuyển sang gọi video
        </button> -->

        <button class="btn btn-danger" @click="hangupCall">Kết thúc</button>
      </div>

      <div class="mt-3" v-show="isCalling && isVideoCall">
        <video ref="localVideo" autoplay muted style="width: 300px"></video>
        <video
          ref="remoteVideo"
          autoplay
          style="width: 300px"
          class="ms-3"
        ></video>
      </div>
    </div>
  </div>
</template>
