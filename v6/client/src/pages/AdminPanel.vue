<template>
  <div class="admin-panel min-h-screen bg-stuzha-bg">
    <AdminPanel />
  </div>
</template>

<script>
import AdminPanel from '../components/AdminPanel.vue';

export default {
  name: 'AdminPanelPage',
  components: {
    AdminPanel
  }
};
</script>