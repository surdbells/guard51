import { NativeScriptConfig } from '@nativescript/core';

export default {
  id: 'com.dosthq.guard51.dispatchermobile',
  appPath: 'src',
  appResourcesPath: 'App_Resources',
  android: {
    v8Flags: '--expose_gc',
    markingMode: 'none',
  },
} as NativeScriptConfig;
