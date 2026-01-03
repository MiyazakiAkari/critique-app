import { createApp } from 'vue';
import App from './App.vue';
import { router } from "./router"
import "./style.css";

// 認証状態の初期化（401レスポンスインターセプター含む）
import "./utils/auth";

createApp(App)
  .use(router)
  .mount('#app');