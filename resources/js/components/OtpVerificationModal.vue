<script setup>
import { ref, watch } from 'vue';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  type: {
    type: String, // 'email' or 'wa'
    required: true,
  },
  target: {
    type: String,
    required: true,
  },
  error: {
    type: String,
    default: null,
  },
  processing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:isOpen', 'submit', 'resend']);

const otp = ref('');

// Auto-focus logic can be added, but keeping it simple for now
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    otp.value = '';
  }
});

const handleSubmit = () => {
  if (otp.value.length === 6) {
    emit('submit', otp.value);
  }
};
</script>

<template>
  <Dialog :open="isOpen" @update:open="$emit('update:isOpen', $event)">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Verifikasi {{ type === 'email' ? 'Email' : 'WhatsApp' }}</DialogTitle>
        <DialogDescription>
          Kami telah mengirimkan 6 digit kode OTP ke <strong>{{ target }}</strong>.
          Silakan masukkan kode tersebut di bawah ini.
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-4 py-4">
        <div class="flex flex-col gap-2">
          <Input 
            v-model="otp" 
            type="text" 
            placeholder="Masukkan 6 digit OTP" 
            maxlength="6"
            class="text-center text-lg tracking-widest font-mono"
            :disabled="processing"
          />
          <p v-if="error" class="text-sm text-red-500 text-center">{{ error }}</p>
        </div>

        <DialogFooter class="flex sm:justify-between items-center gap-4 pt-4">
          <Button 
            type="button" 
            variant="ghost" 
            class="w-full sm:w-auto"
            :disabled="processing"
            @click="$emit('resend')"
          >
            Kirim Ulang Kode
          </Button>
          <Button 
            type="submit" 
            class="w-full sm:w-auto"
            :disabled="otp.length !== 6 || processing"
          >
            <span v-if="processing">Memverifikasi...</span>
            <span v-else>Verifikasi</span>
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
