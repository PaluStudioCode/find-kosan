<script setup>
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Upload, X, FileIcon, ImageIcon } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [File, Array, null],
        default: null
    },
    accept: {
        type: String,
        default: 'image/*'
    },
    multiple: {
        type: Boolean,
        default: false
    },
    maxSize: {
        type: Number,
        default: 5 * 1024 * 1024 // 5MB
    }
});

const emit = defineEmits(['update:modelValue', 'error']);
const fileInput = ref(null);
const previews = ref([]);

const triggerUpload = () => {
    fileInput.value.click();
};

const handleFileChange = (e) => {
    const files = Array.from(e.target.files);
    processFiles(files);
};

const handleDrop = (e) => {
    const files = Array.from(e.dataTransfer.files);
    processFiles(files);
};

const processFiles = (files) => {
    if (!props.multiple && files.length > 1) {
        files = [files[0]];
    }

    const validFiles = [];
    
    files.forEach(file => {
        if (file.size > props.maxSize) {
            emit('error', `Ukuran file ${file.name} melebihi batas (maks ${props.maxSize / 1024 / 1024}MB)`);
            return;
        }
        
        validFiles.push(file);
        
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                if (!props.multiple) previews.value = [e.target.result];
                else previews.value.push(e.target.result);
            };
            reader.readAsDataURL(file);
        } else {
            if (!props.multiple) previews.value = ['file'];
            else previews.value.push('file');
        }
    });

    if (props.multiple) {
        const newValue = [...(Array.isArray(props.modelValue) ? props.modelValue : []), ...validFiles];
        emit('update:modelValue', newValue);
    } else {
        emit('update:modelValue', validFiles[0] || null);
    }
};

const removeFile = (index) => {
    previews.value.splice(index, 1);
    if (props.multiple) {
        const newValue = [...props.modelValue];
        newValue.splice(index, 1);
        emit('update:modelValue', newValue);
    } else {
        emit('update:modelValue', null);
    }
};
</script>

<template>
    <div>
        <div 
            class="border-2 border-dashed rounded-lg p-6 text-center hover:bg-gray-50 transition-colors cursor-pointer"
            @click="triggerUpload"
            @dragover.prevent
            @drop.prevent="handleDrop"
        >
            <input 
                type="file" 
                ref="fileInput" 
                class="hidden" 
                :accept="accept" 
                :multiple="multiple"
                @change="handleFileChange"
            />
            
            <div class="flex flex-col items-center justify-center space-y-2">
                <div class="p-3 bg-primary/10 rounded-full">
                    <Upload class="w-6 h-6 text-primary" />
                </div>
                <div class="text-sm font-medium text-gray-900">
                    Klik untuk upload atau seret file ke sini
                </div>
                <div class="text-xs text-gray-500">
                    Maks {{ maxSize / 1024 / 1024 }}MB. Format: {{ accept }}
                </div>
            </div>
        </div>

        <!-- Previews -->
        <div v-if="previews.length > 0" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
            <div v-for="(preview, index) in previews" :key="index" class="relative group rounded-md border p-2">
                <div v-if="preview === 'file'" class="h-24 flex flex-col items-center justify-center bg-gray-50 rounded">
                    <FileIcon class="w-8 h-8 text-gray-400 mb-2" />
                    <span class="text-xs text-center line-clamp-1 w-full px-2">
                        {{ multiple ? modelValue[index]?.name : modelValue?.name }}
                    </span>
                </div>
                <img v-else :src="preview" class="w-full h-24 object-cover rounded" />
                
                <button 
                    type="button"
                    @click.stop="removeFile(index)"
                    class="absolute -top-2 -right-2 p-1 bg-red-100 text-red-600 rounded-full opacity-0 group-hover:opacity-100 transition-opacity z-10"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>
