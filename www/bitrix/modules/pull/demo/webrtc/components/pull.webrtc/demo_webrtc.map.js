{"version":3,"sources":["demo_webrtc.js"],"names":["window","YourCompanyPrefix","webrtc","params","this","parent","constructor","apply","arguments","debug","callWindowBeforeUnload","placeholder","signalingLink","ready","BX","addCustomEvent","delegate","command","log","senderId","JSON","stringify","callInit","ajax","url","method","dataType","timeout","data","COMMAND","USER_ID","sessid","bitrix_sessid","initiator","callVideo","callUserId","callInitUserId","drawAnswerControls","startGetUserMedia","drawDeclineControls","callDecline","connected","callActive","clearTimeout","pcConnectTimeout","initPeerConnectionTimeout","pc","close","pcStart","callStreamMain","callStreamUsers","initPeerConnection","signalingPeerData","peer","garbage","callCommand","inheritWebrtc","prototype","video","audio","onbeforeunload","message","onUserMediaSuccess","stream","result","attachMediaStream","interfaceVideoSelf","callStreamSelf","muted","addClass","onUserMediaError","error","setLocalAndSend","userId","desc","PEER","onRemoteStreamAdded","event","setMainVideo","interfaceVideoMain","volume","play","onRemoteStreamRemoved","onIceCandidate","candidates","peerConnectionError","peerConnectionReconnect","onsuccess","deleteEvents","pause","removeClass","src","callInvite","interfaceUserId","value","parseInt","drawWaitControls","callAnswer","send","drawInviteControls","async","signalingReady","drawInterface","interface","create","props","className","children","attrs","autoplay","interfaceVideoControls","html","innerHTML","appendChild","adjust","events","click","type"],"mappings":"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","file":""}