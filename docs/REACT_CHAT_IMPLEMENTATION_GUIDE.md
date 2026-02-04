# React Chat Implementation Guide

## Base Configuration

```javascript
const API_BASE_URL = 'https://whatsappapi.celiyo.com/api';
const VENDOR_UID = 'your-vendor-uid';
const API_KEY = 'your-api-key';

const apiClient = axios.create({
  baseURL: `${API_BASE_URL}/${VENDOR_UID}`,
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${API_KEY}`
  }
});
```

---

## 1. Get Contact Messages

### Endpoint
```
GET /api/{vendorUid}/contacts/{contactUid}/messages
```

### Query Parameters
| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| page | integer | 1 | Page number |
| limit | integer | 50 | Messages per page |
| mark_as_read | boolean | true | Mark messages as read |

### Response Structure
```json
{
  "result": "success",
  "message": "Messages fetched successfully",
  "data": {
    "contact": {
      "_uid": "contact-uid",
      "first_name": "John",
      "last_name": "Doe",
      "full_name": "John Doe",
      "wa_id": "919876543210",
      "labels": [],
      "assigned_user": null
    },
    "is_reply_window_open": true,
    "reply_window_expires_at": "2026-02-05T06:00:22+00:00",
    "messages": [
      {
        "_uid": "message-uid",
        "wamid": "wamid.xxx",
        "message": "Hello",
        "message_raw": "Hello",
        "is_incoming_message": true,
        "status": "read",
        "messaged_at": "2026-02-04T05:54:29+00:00",
        "formatted_message_time": "Wednesday 4th February 2026 5:54:29 am",
        "message_type": "text",
        "media_values": null,
        "template_message": null,
        "interaction_message_data": null,
        "is_bot_reply": false,
        "is_ai_bot_reply": false
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 1,
      "per_page": 50,
      "total": 10,
      "has_more": false
    }
  }
}
```

### React Implementation
```javascript
const fetchMessages = async (contactUid, page = 1, limit = 50) => {
  try {
    const response = await apiClient.get(`/contacts/${contactUid}/messages`, {
      params: { page, limit, mark_as_read: true }
    });

    if (response.data.result === 'success') {
      // Messages are returned newest first, reverse for chat display
      const messages = response.data.data.messages.reverse();
      return {
        contact: response.data.data.contact,
        messages,
        pagination: response.data.data.pagination,
        replyWindowOpen: response.data.data.is_reply_window_open
      };
    }
  } catch (error) {
    console.error('Failed to fetch messages:', error);
    throw error;
  }
};
```

---

## 2. Send Text Message

### Endpoint
```
POST /api/{vendorUid}/contacts/{contactUid}/messages
```

### Request Body
```json
{
  "message": "Your message text here"
}
```

### Response
```json
{
  "result": "success",
  "message": "Message sent successfully",
  "data": {
    "log_uid": "message-uid",
    "wamid": "wamid.xxx",
    "status": "accepted",
    "messaged_at": "2026-02-04T06:00:43+00:00"
  }
}
```

### React Implementation
```javascript
const sendTextMessage = async (contactUid, messageText) => {
  try {
    const response = await apiClient.post(`/contacts/${contactUid}/messages`, {
      message: messageText
    });

    if (response.data.result === 'success') {
      // Return the new message data for immediate UI update
      return {
        _uid: response.data.data.log_uid,
        wamid: response.data.data.wamid,
        message: messageText,
        message_raw: messageText,
        is_incoming_message: false,
        status: response.data.data.status,
        messaged_at: response.data.data.messaged_at,
        message_type: 'text'
      };
    }
  } catch (error) {
    console.error('Failed to send message:', error);
    throw error;
  }
};
```

---

## 3. Send Media Message

### Endpoint
```
POST /api/{vendorUid}/contacts/{contactUid}/messages/media
```

### Request Body
```json
{
  "media_type": "image",
  "media_url": "https://example.com/image.jpg",
  "caption": "Optional caption"
}
```

### Media Types
| Type | Supported Formats |
|------|-------------------|
| image | jpg, jpeg, png |
| video | mp4, 3gp |
| audio | mp3, ogg, amr, m4a |
| document | pdf, doc, docx, xls, xlsx, ppt, pptx, txt |

### Response
```json
{
  "result": "success",
  "message": "Media message sent successfully",
  "data": {
    "log_uid": "message-uid",
    "wamid": "wamid.xxx",
    "status": "accepted",
    "messaged_at": "2026-02-04T06:00:43+00:00"
  }
}
```

### React Implementation
```javascript
const sendMediaMessage = async (contactUid, mediaType, mediaUrl, caption = '') => {
  try {
    const response = await apiClient.post(`/contacts/${contactUid}/messages/media`, {
      media_type: mediaType,
      media_url: mediaUrl,
      caption: caption
    });

    if (response.data.result === 'success') {
      return {
        _uid: response.data.data.log_uid,
        wamid: response.data.data.wamid,
        message: caption,
        is_incoming_message: false,
        status: response.data.data.status,
        messaged_at: response.data.data.messaged_at,
        message_type: mediaType,
        media_values: {
          type: mediaType,
          link: mediaUrl,
          caption: caption
        }
      };
    }
  } catch (error) {
    console.error('Failed to send media:', error);
    throw error;
  }
};
```

---

## 4. Send Template Message

### Endpoint
```
POST /api/{vendorUid}/contacts/{contactUid}/messages/template
```

### Request Body
```json
{
  "template_uid": "template-uid",
  "template_components": {
    "header_image_url": "https://example.com/header.jpg",
    "body_variables": ["John", "Order123"]
  }
}
```

### React Implementation
```javascript
const sendTemplateMessage = async (contactUid, templateUid, components = {}) => {
  try {
    const response = await apiClient.post(`/contacts/${contactUid}/messages/template`, {
      template_uid: templateUid,
      template_components: components
    });

    if (response.data.result === 'success') {
      return response.data.data;
    }
  } catch (error) {
    console.error('Failed to send template:', error);
    throw error;
  }
};
```

---

## 5. Complete Chat Component

```jsx
import React, { useState, useEffect, useRef } from 'react';
import axios from 'axios';

const API_BASE_URL = 'https://whatsappapi.celiyo.com/api';
const VENDOR_UID = 'your-vendor-uid';
const API_KEY = 'your-api-key';

const apiClient = axios.create({
  baseURL: `${API_BASE_URL}/${VENDOR_UID}`,
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${API_KEY}`
  }
});

const ChatComponent = ({ contactUid }) => {
  const [messages, setMessages] = useState([]);
  const [contact, setContact] = useState(null);
  const [inputMessage, setInputMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [replyWindowOpen, setReplyWindowOpen] = useState(true);
  const messagesEndRef = useRef(null);

  // Fetch messages on mount and set up polling
  useEffect(() => {
    loadMessages();

    // Poll for new messages every 5 seconds
    const interval = setInterval(loadMessages, 5000);
    return () => clearInterval(interval);
  }, [contactUid]);

  // Auto-scroll to bottom
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages]);

  const loadMessages = async () => {
    try {
      const response = await apiClient.get(`/contacts/${contactUid}/messages`, {
        params: { limit: 100, mark_as_read: true }
      });

      if (response.data.result === 'success') {
        const data = response.data.data;
        setContact(data.contact);
        setReplyWindowOpen(data.is_reply_window_open);
        // Reverse to show oldest first (API returns newest first)
        setMessages(data.messages.reverse());
      }
    } catch (error) {
      console.error('Failed to load messages:', error);
    }
  };

  const sendMessage = async () => {
    if (!inputMessage.trim() || loading) return;

    setLoading(true);
    const messageText = inputMessage;
    setInputMessage('');

    // Optimistic update
    const tempMessage = {
      _uid: `temp-${Date.now()}`,
      message: messageText,
      is_incoming_message: false,
      status: 'sending',
      messaged_at: new Date().toISOString(),
      message_type: 'text'
    };
    setMessages(prev => [...prev, tempMessage]);

    try {
      const response = await apiClient.post(`/contacts/${contactUid}/messages`, {
        message: messageText
      });

      if (response.data.result === 'success') {
        // Replace temp message with real one
        setMessages(prev => prev.map(msg =>
          msg._uid === tempMessage._uid
            ? {
                ...tempMessage,
                _uid: response.data.data.log_uid,
                wamid: response.data.data.wamid,
                status: response.data.data.status,
                messaged_at: response.data.data.messaged_at
              }
            : msg
        ));
      }
    } catch (error) {
      // Mark message as failed
      setMessages(prev => prev.map(msg =>
        msg._uid === tempMessage._uid
          ? { ...msg, status: 'failed' }
          : msg
      ));
    } finally {
      setLoading(false);
    }
  };

  const sendMedia = async (file) => {
    // First upload to your server or use a CDN
    const mediaUrl = await uploadFile(file);

    const mediaType = getMediaType(file.type);

    try {
      const response = await apiClient.post(`/contacts/${contactUid}/messages/media`, {
        media_type: mediaType,
        media_url: mediaUrl,
        caption: ''
      });

      if (response.data.result === 'success') {
        loadMessages(); // Refresh messages
      }
    } catch (error) {
      console.error('Failed to send media:', error);
    }
  };

  const getMediaType = (mimeType) => {
    if (mimeType.startsWith('image/')) return 'image';
    if (mimeType.startsWith('video/')) return 'video';
    if (mimeType.startsWith('audio/')) return 'audio';
    return 'document';
  };

  const formatTime = (isoString) => {
    if (!isoString) return '';
    return new Date(isoString).toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const renderMessage = (msg) => {
    const isOutgoing = !msg.is_incoming_message;

    return (
      <div
        key={msg._uid}
        className={`message ${isOutgoing ? 'outgoing' : 'incoming'}`}
        style={{
          display: 'flex',
          justifyContent: isOutgoing ? 'flex-end' : 'flex-start',
          marginBottom: '10px'
        }}
      >
        <div
          style={{
            maxWidth: '70%',
            padding: '10px 15px',
            borderRadius: '10px',
            backgroundColor: isOutgoing ? '#dcf8c6' : '#ffffff',
            boxShadow: '0 1px 1px rgba(0,0,0,0.1)'
          }}
        >
          {/* Media content */}
          {msg.media_values && (
            <div className="media-content">
              {msg.media_values.type === 'image' && (
                <img
                  src={msg.media_values.link}
                  alt="media"
                  style={{ maxWidth: '100%', borderRadius: '5px' }}
                />
              )}
              {msg.media_values.type === 'video' && (
                <video
                  src={msg.media_values.link}
                  controls
                  style={{ maxWidth: '100%' }}
                />
              )}
              {msg.media_values.type === 'audio' && (
                <audio src={msg.media_values.link} controls />
              )}
              {msg.media_values.type === 'document' && (
                <a href={msg.media_values.link} target="_blank" rel="noreferrer">
                  Download Document
                </a>
              )}
            </div>
          )}

          {/* Text content */}
          {msg.message && <div className="text">{msg.message}</div>}

          {/* Interactive message buttons */}
          {msg.interaction_message_data?.buttons && (
            <div className="buttons" style={{ marginTop: '10px' }}>
              {Object.values(msg.interaction_message_data.buttons).map((btn, i) => (
                <div
                  key={i}
                  style={{
                    padding: '8px',
                    border: '1px solid #ccc',
                    borderRadius: '5px',
                    marginTop: '5px',
                    textAlign: 'center'
                  }}
                >
                  {btn}
                </div>
              ))}
            </div>
          )}

          {/* Timestamp and status */}
          <div
            className="meta"
            style={{
              fontSize: '11px',
              color: '#888',
              marginTop: '5px',
              textAlign: 'right'
            }}
          >
            {formatTime(msg.messaged_at)}
            {isOutgoing && (
              <span style={{ marginLeft: '5px' }}>
                {msg.status === 'sending' && '...'}
                {msg.status === 'accepted' && '✓'}
                {msg.status === 'sent' && '✓'}
                {msg.status === 'delivered' && '✓✓'}
                {msg.status === 'read' && '✓✓'}
                {msg.status === 'failed' && '!'}
              </span>
            )}
          </div>
        </div>
      </div>
    );
  };

  return (
    <div className="chat-container" style={{ height: '100vh', display: 'flex', flexDirection: 'column' }}>
      {/* Header */}
      <div className="chat-header" style={{ padding: '15px', borderBottom: '1px solid #eee' }}>
        <h3>{contact?.full_name || 'Loading...'}</h3>
        <span>{contact?.wa_id}</span>
        {!replyWindowOpen && (
          <div style={{ color: 'orange', fontSize: '12px' }}>
            Reply window closed - Can only send templates
          </div>
        )}
      </div>

      {/* Messages */}
      <div
        className="messages-container"
        style={{
          flex: 1,
          overflowY: 'auto',
          padding: '20px',
          backgroundColor: '#e5ddd5'
        }}
      >
        {messages.map(renderMessage)}
        <div ref={messagesEndRef} />
      </div>

      {/* Input */}
      <div
        className="input-container"
        style={{
          padding: '15px',
          borderTop: '1px solid #eee',
          display: 'flex',
          gap: '10px'
        }}
      >
        {/* File upload */}
        <input
          type="file"
          id="file-input"
          style={{ display: 'none' }}
          onChange={(e) => e.target.files[0] && sendMedia(e.target.files[0])}
        />
        <button onClick={() => document.getElementById('file-input').click()}>
          📎
        </button>

        {/* Text input */}
        <input
          type="text"
          value={inputMessage}
          onChange={(e) => setInputMessage(e.target.value)}
          onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
          placeholder="Type a message..."
          style={{
            flex: 1,
            padding: '10px',
            borderRadius: '20px',
            border: '1px solid #ccc'
          }}
          disabled={!replyWindowOpen}
        />

        <button
          onClick={sendMessage}
          disabled={loading || !replyWindowOpen}
          style={{
            padding: '10px 20px',
            borderRadius: '20px',
            backgroundColor: '#25d366',
            color: 'white',
            border: 'none',
            cursor: 'pointer'
          }}
        >
          Send
        </button>
      </div>
    </div>
  );
};

export default ChatComponent;
```

---

## 6. Message Field Reference

| Field | Type | Description |
|-------|------|-------------|
| `_uid` | string | Unique message identifier |
| `wamid` | string | WhatsApp message ID |
| `message` | string | Message text content |
| `message_raw` | string | Raw message without formatting |
| `is_incoming_message` | boolean | `true` = from customer, `false` = from business |
| `status` | string | `accepted`, `sent`, `delivered`, `read`, `failed` |
| `messaged_at` | string | ISO 8601 timestamp |
| `message_type` | string | `text`, `image`, `video`, `audio`, `document` |
| `media_values` | object | Media details (type, link, caption) |
| `template_message` | string | HTML preview for template messages |
| `interaction_message_data` | object | Button/list data for interactive messages |
| `is_bot_reply` | boolean | Message sent by bot |
| `is_ai_bot_reply` | boolean | Message sent by AI bot |

---

## 7. Error Handling

```javascript
const handleApiError = (error) => {
  if (error.response) {
    switch (error.response.status) {
      case 401:
        // Invalid API key
        console.error('Authentication failed');
        break;
      case 404:
        // Contact not found
        console.error('Contact not found');
        break;
      case 422:
        // Validation error
        console.error('Validation error:', error.response.data);
        break;
      default:
        console.error('API error:', error.response.data);
    }
  }
};
```

---

## 8. WebSocket Integration (Optional)

For real-time updates, integrate with Laravel Echo:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
  broadcaster: 'pusher',
  key: 'your-pusher-key',
  cluster: 'your-cluster',
  forceTLS: true
});

// Listen for new messages
echo.channel(`vendor.${VENDOR_UID}`)
  .listen('VendorChannelBroadcast', (event) => {
    if (event.contactUid === currentContactUid) {
      loadMessages(); // Refresh messages
    }
  });
```

---

## 9. Important Notes

1. **Reply Window**: WhatsApp only allows free-form messages within 24 hours of last customer message. After that, only template messages can be sent.

2. **Message Order**: API returns messages newest first. Reverse the array for chronological display.

3. **Timestamps**: All timestamps are in ISO 8601 format (UTC). Convert to local timezone for display.

4. **Media URLs**: For sending media, URLs must be publicly accessible.

5. **Rate Limits**: Respect WhatsApp's rate limits to avoid getting blocked.
