<script setup>
import { ref, onMounted, watch } from 'vue';
import { useBase, useHttp } from "../lib";

const { clickFile, uploadFile, getImage,openFile,fileIcon } = { ...useHttp(), ...useBase() };

const props = defineProps({
  formObject: { type: Object, required: true },
  column: { type: String, default: 'attachment' },
  height: { type: Number, default: 120 },
  divClass: { type: String, default: 'col-md-4' }
});

const attachments = ref([]);

// Initialize attachments from formObject
// const initializeAttachments = () => {
//   const arr = props.formObject[props.column] || [];
//
//   // Convert array of strings/objects to our slot format
//   attachments.value = arr.map(item => {
//     if (typeof item === 'string') {
//       return { [props.column]: item };
//     }
//     if (typeof item === 'object' && item[props.column]) {
//       return { [props.column]: item[props.column] };
//     }
//     if (typeof item === 'object' && item.url) {
//       return { [props.column]: item.url };
//     }
//     return { [props.column]: item || '' };
//   });
//
//   ensureEmptySlot();
// };

const initializeAttachments = () => {
    let arr = props.formObject?.[props.column];

    // Ensure arr is always an array
    if (!Array.isArray(arr)) {
        if (!arr) {
            arr = [];
        } else {
            // single value → convert to array
            arr = [arr];
        }
    }

    attachments.value = arr.map(item => {
        if (typeof item === 'string') {
            return { [props.column]: item };
        }

        if (typeof item === 'object' && item?.[props.column]) {
            return { [props.column]: item[props.column] };
        }

        if (typeof item === 'object' && item?.url) {
            return { [props.column]: item.url };
        }

        return { [props.column]: item || '' };
    });

    ensureEmptySlot();
};

const ensureEmptySlot = () => {
  // Always ensure there's at least one empty slot at the end
  const hasEmptySlot = attachments.value.some(slot =>
    !slot[props.column] || slot[props.column] === ''
  );

  if (!hasEmptySlot) {
    attachments.value.push({ [props.column]: '' });
  }
};

const removeSlot = (index) => {
  attachments.value.splice(index, 1);
  ensureEmptySlot();
  updateFormObject();
};

const handleFileChange = async (event, slot, index) => {
  try {
    await uploadFile(event, {
      imageObject: slot,
      dataModel: props.column
    });
    if (slot[props.column]) {
      attachments.value[index] = { ...slot };
      ensureEmptySlot();
      updateFormObject();
    }
  } catch (error) {
    console.error('Upload failed:', error);
  }
};

const updateFormObject = () => {
  const validAttachments = attachments.value
    .filter(slot => slot[props.column] && slot[props.column] !== '')
    .map(slot => {
      const val = slot[props.column];
      if (typeof val === 'string') return val;
      if (typeof val === 'object' && val.url) return val.url;
      return val;
    })
    .filter(val => val !== null && val !== '');

  props.formObject[props.column] = validAttachments;
};

// Watch for changes in formObject from parent
watch(
  () => props.formObject[props.column],
  (newVal) => {
    const currentAttachments = attachments.value
      .filter(slot => slot[props.column] && slot[props.column] !== '')
      .map(slot => slot[props.column]);

    if (JSON.stringify(newVal) !== JSON.stringify(currentAttachments)) {
      initializeAttachments();
    }
  },
  { deep: true }
);

const getImageUrl = (slot) => {
  const value = slot[props.column];
  if (!value || value === '') return '../backend/images/upload.png';

  if (typeof value === 'string') {
    return getImage(value, '../backend/images/upload.png');
  }

  if (typeof value === 'object' && value.url) {
    return getImage(value.url, '../backend/images/upload.png');
  }

  return '../backend/images/upload.png';
};

const getFileName = (slot) => {
  const value = slot[props.column];
  if (!value || value === '') return '';

  if (typeof value === 'string') {
    return value.split('/').pop() || 'Uploaded File';
  }

  if (typeof value === 'object') {
    return value.name || value.filename || 'Uploaded File';
  }

  return 'Uploaded File';
};

onMounted(() => {
  initializeAttachments();
});
</script>

<template>
  <div class="multiple-file-upload">
    <div class="row mb-2">
      <div :class="`${divClass} mb-3`" v-for="(slot, index) in attachments" :key="index">
        <!-- Uploaded file display -->
        <div v-if="slot[column] && slot[column] !== ''" class="p-2 border rounded text-center uploaded-file">
          <div class="file-name">
            <strong>{{ getFileName(slot) }}</strong>
          </div>
          <div class="mt-2 file-preview">
            <img :src="fileIcon(getFileName(slot))" :alt="getFileName(slot)" @click="openFile(getImageUrl(slot))" :style="{maxHeight: `${height}px`, maxWidth: '100%',objectFit: 'contain'}"
            />
          </div>
          <a class="attachment_remove text-danger" @click="removeSlot(index)">X</a>
        </div>

        <!-- Empty slot for new upload -->
        <!-- @click="clickFile('file' + index)"-->

        <div v-else
          class="drop_zone pointer"
             @click="clickFile(`${column}-file-${index}`)"
          :style="{
            height: `${height}px`,
            backgroundImage: `url('/backend/images/upload.png')`,
            backgroundSize: 'contain',
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
            border: '2px dashed #ccc',
            borderRadius: '5px'
          }"
        >
          <input type="file" :id="`${column}-file-${index}`" class="form-control" style="display: none"
            @change="handleFileChange($event, slot, index)"/>
          <div class="upload-placeholder" style="padding-top: 40px; text-align: center; color: #666;">
            Click to Upload <br> Attachments
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.multiple-file-upload .col-md-4 {
  min-height: v-bind(height + 40 + 'px');
}

.drop_zone {
  cursor: pointer;
  position: relative;
}

.uploaded-file {
  background-color: #f8f9fa;
}

.file-name {
  word-break: break-word;
  font-size: 0.9em;
}

.file-preview img {
  border-radius: 3px;
}

.pointer {
  cursor: pointer;
}

.upload-placeholder {
  font-size: 0.9em;
}
.textDesign {
  display: block;
  color: #333;
  word-break: break-word;
}
</style>
