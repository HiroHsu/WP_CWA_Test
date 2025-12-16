import React, { useState } from 'react';
import { Settings, X } from 'lucide-react';

interface SettingsModalProps {
  apiKey: string;
  onSave: (key: string) => void;
  isOpen: boolean;
  onClose: () => void;
}

export const SettingsModal: React.FC<SettingsModalProps> = ({ apiKey, onSave, isOpen, onClose }) => {
  const [inputKey, setInputKey] = useState(apiKey);

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div className="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div className="flex justify-between items-center p-4 border-b">
          <h2 className="text-xl font-bold flex items-center gap-2 text-gray-800">
            <Settings className="w-5 h-5" />
            設定
          </h2>
          <button onClick={onClose} className="p-1 hover:bg-gray-100 rounded-full transition-colors">
            <X className="w-6 h-6 text-gray-500" />
          </button>
        </div>
        
        <div className="p-6">
          <div className="mb-4">
            <label htmlFor="cwa-key" className="block text-sm font-medium text-gray-700 mb-2">
              CWA Open Data API Key (Authorization)
            </label>
            <input
              id="cwa-key"
              type="text"
              value={inputKey}
              onChange={(e) => setInputKey(e.target.value)}
              placeholder="e.g., CWA-XXXX-XXXX-XXXX"
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
            />
            <p className="mt-2 text-xs text-gray-500">
              您需要從 <a href="https://opendata.cwa.gov.tw/" target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">氣象署開放資料平台</a> 獲取授權碼。
            </p>
          </div>
          
          <button
            onClick={() => {
              onSave(inputKey);
              onClose();
            }}
            className="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
          >
            儲存並重新整理
          </button>
        </div>
      </div>
    </div>
  );
};
